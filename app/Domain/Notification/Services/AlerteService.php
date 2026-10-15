<?php

namespace App\Domain\Notification\Services;

use App\Domain\Acces\Enums\PermissionEnum;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Commission\Services\ReglementService;
use App\Domain\Demande\Models\Demande;
use App\Domain\Notification\Notifications\SuiviDossierNotification;
use App\Domain\Stock\Models\Appui;
use App\Models\User;
use App\Utils\Helper;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * Les notifications qui comptent, et seulement celles-là : un message par
 * jalon et par destinataire, jamais un par ligne ni par modification.
 *
 *  - résultats publiés   → chaque DP concerné, et les magasiniers (bons à servir) ;
 *  - appui sous son seuil → les magasiniers, une fois, au franchissement ;
 *  - résumé quotidien    → agents de service et de commission, s'il y a de quoi dire ;
 *  - rappels hebdomadaires → ce qui traîne : impayés, bons non retirés, brouillons oubliés, séances ouvertes.
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
     * Résultats d'une séance publiés : un message par DP responsable (les demandes de
     * ses départements attribuées et rejetées) et un message aux magasiniers (bons à servir).
     */
    public function resultatsPublies(SessionCommission $session): void
    {
        $date = $session->date_debut_session->format('d/m/Y');

        $attribuees = Attribution::where('id_session', $session->id_session)
            ->whereNotNull('date_publication')
            ->with('demande.localites')
            ->get()
            ->pluck('demande')
            ->filter();

        $rejetees = Demande::where('id_session', $session->id_session)
            ->whereNotNull('motifs_rejet')
            ->with('localites')
            ->get();

        // Pour chaque DP responsable : combien de ses demandes sont attribuées, combien rejetées.
        $parDp = [];
        foreach (['attribuees' => $attribuees, 'rejetees' => $rejetees] as $issue => $demandes) {
            foreach ($demandes as $demande) {
                foreach ($demande->responsables() as $dp) {
                    $parDp[$dp->id]['dp'] = $dp;
                    $parDp[$dp->id][$issue] = ($parDp[$dp->id][$issue] ?? 0) + 1;
                }
            }
        }

        foreach ($parDp as $ligne) {
            $dp = $ligne['dp'];
            $nbAttribuees = $ligne['attribuees'] ?? 0;
            $nbRejetees = $ligne['rejetees'] ?? 0;

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
    public function stockSousSeuil(Appui $appui, float|int|string $stock): void
    {
        $unite = $appui->uniteDosage?->lib_unite;

        Notification::send($this->magasiniers(), new SuiviDossierNotification(
            titre: "Stock bas : {$appui->libelle}",
            message: 'Il reste '.Helper::quantite($stock).($unite ? " {$unite}" : '')." (seuil d'alerte : ".Helper::quantite($appui->seuil_alerte).'). Pensez à réapprovisionner.',
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

    /**
     * Les rappels de la semaine : chacun ne part que s'il y a quelque chose qui traîne,
     * à celui qui peut agir. Un message par destinataire et par sujet.
     *
     * @return array<string, int> destinataires notifiés, par sujet
     */
    public function rappelsHebdomadaires(int $joursBons = 14, int $joursBrouillons = 7, int $joursSeances = 30): array
    {
        return [
            'impayes' => $this->rappelImpayes(),
            'bons' => $this->rappelBonsNonRetires($joursBons),
            'brouillons' => $this->rappelBrouillons($joursBrouillons),
            'seances' => $this->rappelSeancesOuvertes($joursSeances),
        ];
    }

    /** Aux administrateurs : les acteurs qui n'ont pas fini de régler, et ce qu'il reste à encaisser. */
    public function rappelImpayes(): int
    {
        $acteurs = app(ReglementService::class)->acteursEnImpaye();

        if ($acteurs->isEmpty()) {
            return 0;
        }

        $reste = number_format($acteurs->sum('reste_du'), 0, ',', ' ');
        $destinataires = User::permission(PermissionEnum::gererReglements->value)->get();

        Notification::send($destinataires, new SuiviDossierNotification(
            titre: 'Règlements en attente',
            message: "{$acteurs->count()} acteur(s) n'ont pas fini de régler leurs attributions : {$reste} F restent à encaisser. Ils ne peuvent pas déposer de nouvelle demande.",
            urlAction: route('dashboard'),
            type: 'warning',
            canaux: self::CANAUX_RESUME,
        ));

        return $destinataires->count();
    }

    /** Aux magasiniers : les bons publiés depuis plus de $jours jours et jamais retirés. */
    public function rappelBonsNonRetires(int $jours): int
    {
        $bons = Attribution::aDestocker()->where('date_publication', '<=', now()->subDays($jours))->count();

        if ($bons === 0) {
            return 0;
        }

        $magasiniers = $this->magasiniers();

        Notification::send($magasiniers, new SuiviDossierNotification(
            titre: 'Bons en attente de retrait',
            message: "{$bons} bon(s) publié(s) depuis plus de {$jours} jours n'ont pas été retirés au magasin.",
            urlAction: route('enlevements.index'),
            type: 'warning',
            canaux: self::CANAUX_RESUME,
        ));

        return $magasiniers->count();
    }

    /** À chaque DP : ses brouillons de demande restés sans suite depuis plus de $jours jours. */
    public function rappelBrouillons(int $jours): int
    {
        $parDp = Demande::whereNull('date_soummission')
            ->whereNull('motifs_rejet')
            ->where('created_at', '<=', now()->subDays($jours))
            ->get()
            ->groupBy('id_user');

        $envoyes = 0;

        foreach (User::whereIn('id', $parDp->keys()->filter())->get() as $dp) {
            $dp->notify(new SuiviDossierNotification(
                titre: 'Brouillons à terminer',
                message: "{$parDp[$dp->id]->count()} demande(s) en brouillon depuis plus de {$jours} jours n'ont pas été soumises.",
                urlAction: route('demandes.index'),
                type: 'info',
                canaux: self::CANAUX_RESUME,
            ));
            $envoyes++;
        }

        return $envoyes;
    }

    /** À la commission : les séances ouvertes depuis plus de $jours jours, qu'il faudrait clôturer. */
    public function rappelSeancesOuvertes(int $jours): int
    {
        $seances = SessionCommission::whereNull('date_fin_session')
            ->where('date_debut_session', '<=', now()->subDays($jours))
            ->count();

        if ($seances === 0) {
            return 0;
        }

        $agents = User::role(RoleEnum::agentCommission->value)->get();

        Notification::send($agents, new SuiviDossierNotification(
            titre: 'Séances à clôturer',
            message: "{$seances} séance(s) de commission sont ouvertes depuis plus de {$jours} jours.",
            urlAction: route('commissions.index'),
            type: 'warning',
            canaux: self::CANAUX_RESUME,
        ));

        return $agents->count();
    }

    private function magasiniers()
    {
        return User::role(RoleEnum::gestionnaireStock->value)->get();
    }
}
