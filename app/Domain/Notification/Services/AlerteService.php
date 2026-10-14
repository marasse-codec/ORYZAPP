<?php

namespace App\Domain\Notification\Services;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Demande\Models\Demande;
use App\Domain\Notification\Notifications\SuiviDossierNotification;
use App\Domain\Stock\Models\Appui;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * Les notifications qui comptent, et seulement celles-là : un message par
 * jalon et par destinataire, jamais un par ligne ni par modification.
 *
 *  - résultats publiés   → chaque DP concerné, et les magasiniers (bons à servir) ;
 *  - appui sous son seuil → les magasiniers, une fois, au franchissement ;
 *  - résumé quotidien    → agents de service et de commission, s'il y a de quoi dire.
 *
 * À appeler APRÈS la validation de la transaction qui a produit l'événement :
 * on n'annonce que ce qui est acquis. Les envois partent en file d'attente.
 */
class AlerteService
{
    /** Cloche + push + mail : pour ce qui demande une réaction. */
    private const CANAUX_URGENTS = ['database', WebPushChannel::class, 'mail'];

    /** Cloche + mail : pour le résumé, qui n'a pas à faire sonner un téléphone. */
    private const CANAUX_RESUME = ['database', 'mail'];

    /**
     * Résultats d'une séance publiés : un message par DP (ses demandes
     * attribuées et rejetées) et un message aux magasiniers (bons à servir).
     */
    public function resultatsPublies(SessionCommission $session): void
    {
        $date = $session->date_debut_session->format('d/m/Y');

        $attribuees = Attribution::where('id_session', $session->id_session)
            ->whereNotNull('date_publication')
            ->with('demande')
            ->get()
            ->groupBy(fn (Attribution $a) => $a->demande?->id_user);

        $rejetees = Demande::where('id_session', $session->id_session)
            ->whereNotNull('motifs_rejet')
            ->get()
            ->groupBy('id_user');

        $idsDp = $attribuees->keys()->merge($rejetees->keys())->filter()->unique();

        foreach (User::whereIn('id', $idsDp)->get() as $dp) {
            $nbAttribuees = $attribuees->get($dp->id)?->count() ?? 0;
            $nbRejetees = $rejetees->get($dp->id)?->count() ?? 0;

            $dp->notify(new SuiviDossierNotification(
                titre: 'Résultats de la commission publiés',
                message: "Séance du {$date} : {$nbAttribuees} de vos demandes attribuée(s), {$nbRejetees} rejetée(s). Vous pouvez informer les demandeurs.",
                urlAction: route('attributions.index'),
                type: 'success',
                estUrgente: false,
                canaux: self::CANAUX_URGENTS,
            ));
        }

        $bonsAServir = Attribution::aDestocker()->where('id_session', $session->id_session)->count();

        if ($bonsAServir > 0) {
            Notification::send($this->magasiniers(), new SuiviDossierNotification(
                titre: 'Bons à servir',
                message: "Résultats de la séance du {$date} publiés : {$bonsAServir} bon(s) à servir au magasin.",
                urlAction: route('enlevements.index'),
                type: 'info',
                estUrgente: true,
                canaux: self::CANAUX_URGENTS,
            ));
        }
    }

    /**
     * Un appui vient de passer sous son seuil d'alerte (et pas seulement d'y
     * rester) : les magasiniers en sont avertis une fois.
     */
    public function stockSousSeuil(Appui $appui, int $stock): void
    {
        $unite = $appui->uniteDosage?->lib_unite;

        Notification::send($this->magasiniers(), new SuiviDossierNotification(
            titre: "Stock bas : {$appui->libelle}",
            message: "Il reste {$stock}".($unite ? " {$unite}" : '')." (seuil d'alerte : {$appui->seuil_alerte}). Pensez à réapprovisionner.",
            urlAction: route('approvisionnements.create'),
            type: 'warning',
            estUrgente: true,
            canaux: self::CANAUX_URGENTS,
        ));
    }

    /** Vrai quand un stock passe d'au-dessus à au niveau (ou en dessous) du seuil, seuil actif. */
    public static function franchitLeSeuil(Appui $appui, $avant, $apres): bool
    {
        return $appui->seuil_alerte > 0 && $avant > $appui->seuil_alerte && $apres <= $appui->seuil_alerte;
    }

    /**
     * Le résumé de la veille pour ceux qui n'ont pas à être alertés en continu.
     * Un message par destinataire, seulement s'il y a quelque chose à lui dire.
     *
     * @return int nombre de destinataires notifiés
     */
    public function resumeQuotidien(): int
    {
        $depuis = now()->subDay();
        $envoyes = 0;

        // Agents de service : les demandes qui les concernent et leurs attributions publiées.
        foreach (User::role(RoleEnum::agentService->value)->whereNotNull('id_service')->get() as $agent) {
            $demandes = Demande::where('date_soummission', '>=', $depuis)
                ->whereHas('lignes.typeAppui', fn ($q) => $q->where('id_service', $agent->id_service))
                ->count();
            $attributions = Attribution::where('date_publication', '>=', $depuis)
                ->whereHas('lignesAttributions.appui.typeAppui', fn ($q) => $q->where('id_service', $agent->id_service))
                ->count();

            if ($demandes + $attributions === 0) {
                continue;
            }

            $agent->notify(new SuiviDossierNotification(
                titre: 'Résumé quotidien',
                message: "Depuis hier : {$demandes} nouvelle(s) demande(s) pour votre service, {$attributions} attribution(s) publiée(s).",
                urlAction: route('demandes.index'),
                type: 'info',
                canaux: self::CANAUX_RESUME,
            ));
            $envoyes++;
        }

        // Agents de commission : ce qui attend leur arbitrage ou leur publication.
        $aArbitrer = Demande::aArbitrer()->count();
        $aPublier = Attribution::aPublier()->count();

        if ($aArbitrer + $aPublier > 0) {
            $agents = User::role(RoleEnum::agentCommission->value)->get();

            Notification::send($agents, new SuiviDossierNotification(
                titre: 'Résumé quotidien',
                message: "{$aArbitrer} demande(s) à arbitrer, {$aPublier} attribution(s) à publier.",
                urlAction: route('commissions.index'),
                type: 'info',
                canaux: self::CANAUX_RESUME,
            ));
            $envoyes += $agents->count();
        }

        return $envoyes;
    }

    private function magasiniers()
    {
        return User::role(RoleEnum::gestionnaireStock->value)->get();
    }
}
