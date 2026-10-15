<?php

namespace App\Domain\Commission\Services;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\LigneAttribution;
use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Commission\Models\SuperficieAttribution;
use App\Domain\Demande\Models\Demande;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Journal\Support\Ecarts;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Domain\Stock\Models\Appui;
use Illuminate\Support\Facades\DB;

/**
 * Les attributions de la commission : les enregistrer dans une séance ouverte,
 * les modifier, les annuler. Tant que la séance est ouverte, tout est possible ;
 * sa clôture vaut validation et fige l'attribution.
 *
 * Chaque opération est une transaction qui verrouille la séance (et la demande
 * pour une création) : une clôture simultanée, ou deux attributions de la même
 * demande, ne peuvent pas se croiser. La trace au journal part avec l'opération.
 */
class AttributionService
{
    /**
     * @param  array<int, array{id_appui: int|string, quantite_attribuee: int|float|string, cout_attribution?: mixed, remise?: mixed}>  $lignes
     */
    public function enregistrer(Demande $demande, int|string|null $idSession, array $lignes, ?string $commentaires = null, array $superficies = []): Attribution
    {
        Campagne::exigerOuverteParId($demande->id_campagne, 'attribuer des appuis');

        return DB::transaction(function () use ($demande, $idSession, $lignes, $commentaires, $superficies) {
            $session = $idSession ? SessionCommission::whereKey($idSession)->lockForUpdate()->first() : null;

            if (! $session?->estOuverte()) {
                throw new RegleMetierViolee("Aucune séance ouverte : ouvrez une séance avant d'attribuer.");
            }

            // La demande est verrouillée : deux attributions simultanées ne peuvent pas porter sur elle.
            $attribuable = Demande::query()->aArbitrer()->whereKey($demande->id_demande)->lockForUpdate()->exists();

            if (! $attribuable) {
                throw new RegleMetierViolee("Cette demande n'attend pas d'arbitrage : elle est déjà attribuée ou rejetée, ou n'a pas été soumise.");
            }

            $attribution = Attribution::create([
                'id_demande' => $demande->id_demande,
                'id_session' => $session->id_session,
                'commentaires' => $commentaires,
            ]);

            $this->enregistrerLignes($attribution, $lignes);
            $this->enregistrerSuperficies($attribution, $superficies);

            JournalAction::enregistrer(ActionJournalEnum::attributionCreee, $attribution, ['id_demande' => $demande->id_demande]);

            return $attribution;
        });
    }

    /**
     * Remplace les lignes, les superficies traitées et le commentaire de l'attribution.
     *
     * @param  array<int|string, int|float|string|null>  $superficies  id_type_systeme_production => superficie traitée
     */
    public function modifier(Attribution $attribution, array $lignes, ?string $commentaires = null, array $superficies = []): Attribution
    {
        return DB::transaction(function () use ($attribution, $lignes, $commentaires, $superficies) {
            $this->exigerSeanceOuverte($attribution, 'modifiée');
            Campagne::exigerOuverteParId($attribution->demande?->id_campagne, 'modifier une attribution');

            // Le contenu avant modification, pour que le journal dise ce qui a changé.
            $avant = $this->instantane($attribution);

            $attribution->lignesAttributions()->delete();
            $this->enregistrerLignes($attribution, $lignes);
            $attribution->superficies()->delete();
            $this->enregistrerSuperficies($attribution, $superficies);
            $attribution->update(['commentaires' => $commentaires]);

            JournalAction::enregistrer(ActionJournalEnum::attributionModifiee, $attribution, Ecarts::entre($avant, $this->instantane($attribution->fresh())));

            return $attribution;
        });
    }

    public function annuler(Attribution $attribution): void
    {
        DB::transaction(function () use ($attribution) {
            $this->exigerSeanceOuverte($attribution, 'annulée');
            Campagne::exigerOuverteParId($attribution->demande?->id_campagne, 'annuler une attribution');

            // Suppression douce : l'attribution est marquée annulée, son contenu reste en base.
            // Le journal en garde aussi une copie lisible.
            JournalAction::enregistrer(ActionJournalEnum::attributionSupprimee, $attribution, ['id_demande' => $attribution->id_demande] + $this->instantane($attribution));

            $attribution->delete();
        });
    }

    /**
     * Le contenu d'une attribution en texte simple (appui => quantité × coût, remise),
     * pour comparer avant/après au journal et garder une copie d'une attribution annulée.
     *
     * @return array<string, string|null>
     */
    private function instantane(Attribution $attribution): array
    {
        $attribution->load('lignesAttributions.appui', 'superficies');

        $lignes = $attribution->lignesAttributions->mapWithKeys(fn (LigneAttribution $ligne) => [
            'appui '.($ligne->appui?->libelle ?? $ligne->id_appui) => sprintf(
                '%s × %s F, remise %s %%',
                (float) $ligne->quantite_attribuee,
                (float) $ligne->cout_attribution,
                (float) $ligne->remise,
            ),
        ]);

        $superficies = $attribution->superficies->mapWithKeys(fn (SuperficieAttribution $s) => [
            "superficie écologie {$s->id_type_systeme_production}" => (float) $s->superficie_traitee.' ha',
        ]);

        return $lignes->merge($superficies)->put('commentaires', $attribution->commentaires)->all();
    }

    /** La séance est relue sous verrou : la clôture qui la fige ne peut pas passer entre le contrôle et l'écriture. */
    private function exigerSeanceOuverte(Attribution $attribution, string $action): void
    {
        $session = SessionCommission::whereKey($attribution->id_session)->lockForUpdate()->first();

        if (! $session?->estOuverte()) {
            throw new RegleMetierViolee("La séance est clôturée : cette attribution est validée et ne peut plus être {$action}.");
        }
    }

    /** Une ligne par écologie à superficie positive, comme pour les superficies d'une demande. */
    private function enregistrerSuperficies(Attribution $attribution, array $superficies): void
    {
        foreach ($superficies as $idType => $superficie) {
            if ((float) $superficie > 0) {
                SuperficieAttribution::create([
                    'id_attribution' => $attribution->id_attribution,
                    'id_type_systeme_production' => $idType,
                    'superficie_traitee' => $superficie,
                ]);
            }
        }
    }

    private function enregistrerLignes(Attribution $attribution, array $lignes): void
    {
        foreach ($lignes as $ligne) {
            LigneAttribution::create([
                'id_attribution' => $attribution->id_attribution,
                'id_appui' => $ligne['id_appui'],
                'quantite_attribuee' => $ligne['quantite_attribuee'],
                // Sans coût saisi, on fige le coût unitaire du catalogue : le bon garde le prix du jour de
                // l'attribution, même si le catalogue change ensuite.
                'cout_attribution' => $ligne['cout_attribution'] ?? Appui::whereKey($ligne['id_appui'])->value('cout_unitaire'),
                'remise' => $ligne['remise'] ?? 0,
            ]);
        }
    }
}
