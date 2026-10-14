<?php

namespace App\Domain\Suivi\Services;

use App\Domain\Demande\Enums\StatutDemandeE;
use App\Domain\Demande\Models\Demande;
use App\Domain\Referentiel\Models\Campagne;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Les statistiques d'une campagne, pour l'administration.
 *
 * Lecture seule : aucune écriture, donc aucune transaction à ouvrir. Les
 * agrégats se font en SQL (COUNT, SUM, GROUP BY, portables MySQL/SQLite) ;
 * seuls les délais se calculent en PHP, sur deux dates par dossier.
 *
 * Périmètre : les demandes soumises (un brouillon n'est pas une demande à
 * satisfaire) et rattachées à la campagne. Les dons directs, sans demande,
 * n'y figurent donc pas.
 */
class StatistiqueCampagneService
{
    /** Demandes soumises de la campagne, en requête brute pour joindre à volonté. */
    private function demandesSoumises(Campagne $campagne): Builder
    {
        return DB::table('demandes')
            ->where('demandes.id_campagne', $campagne->id_campagne)
            ->whereNotNull('demandes.date_soummission');
    }

    /**
     * Chiffres clés : volumes, taux d'attribution, bénéficiaires, délais.
     *
     * @return array{soumises: int, attribuees: int, rejetees: int, taux_attribution: ?float, beneficiaires: int, delai_arbitrage: ?float, delai_retrait: ?float, superficie_concernee: float, superficie_traitee: float, taux_traitement: ?float}
     */
    public function indicateurs(Campagne $campagne): array
    {
        $parStatut = $this->parStatut($campagne);

        $soumises = array_sum($parStatut) - $parStatut[StatutDemandeE::brouillon->value];
        $attribuees = $parStatut[StatutDemandeE::recu_attribution->value];

        $attributions = $this->demandesSoumises($campagne)
            ->join('attributions', 'attributions.id_demande', '=', 'demandes.id_demande')
            ->get(['demandes.id_acteur', 'demandes.date_soummission', 'attributions.created_at', 'attributions.date_destockage']);

        $superficies = $this->superficiesParEcologie($campagne);
        $concernee = (float) $superficies->sum('concernee');
        $traitee = (float) $superficies->sum('traitee');

        return [
            'soumises' => $soumises,
            'attribuees' => $attribuees,
            'rejetees' => $parStatut[StatutDemandeE::rejetee->value],
            'taux_attribution' => $soumises > 0 ? round($attribuees / $soumises * 100, 1) : null,
            'beneficiaires' => $attributions->pluck('id_acteur')->unique()->count(),
            'delai_arbitrage' => $this->delaiMoyen($attributions, 'date_soummission', 'created_at'),
            'delai_retrait' => $this->delaiMoyen($attributions, 'created_at', 'date_destockage'),
            'superficie_concernee' => $concernee,
            'superficie_traitee' => $traitee,
            'taux_traitement' => $concernee > 0 ? round($traitee / $concernee * 100, 1) : null,
        ];
    }

    /**
     * Par écologie : superficie déclarée par les demandes soumises (concernée) et superficie que les
     * attributions permettent de traiter. Deux agrégats séparés, fusionnés : les joindre multiplierait les lignes.
     *
     * @return Collection<string, array{concernee: float, traitee: float}> libellé de l'écologie => hectares
     */
    public function superficiesParEcologie(Campagne $campagne): Collection
    {
        $concernees = $this->demandesSoumises($campagne)
            ->join('systemes_productions', 'systemes_productions.id_demande', '=', 'demandes.id_demande')
            ->groupBy('systemes_productions.id_type_systeme_production')
            ->selectRaw('systemes_productions.id_type_systeme_production AS id_type, SUM(systemes_productions.superficie) AS total')
            ->pluck('total', 'id_type');

        $traitees = $this->demandesSoumises($campagne)
            ->join('attributions', 'attributions.id_demande', '=', 'demandes.id_demande')
            ->join('attributions_systemes_productions', 'attributions_systemes_productions.id_attribution', '=', 'attributions.id_attribution')
            ->groupBy('attributions_systemes_productions.id_type_systeme_production')
            ->selectRaw('attributions_systemes_productions.id_type_systeme_production AS id_type, SUM(attributions_systemes_productions.superficie_traitee) AS total')
            ->pluck('total', 'id_type');

        return DB::table('types_systemes_productions')
            ->orderBy('id_type_systeme_production')
            ->get(['id_type_systeme_production', 'lib_type_systeme_production'])
            ->mapWithKeys(fn ($type) => [$type->lib_type_systeme_production => [
                'concernee' => (float) ($concernees[$type->id_type_systeme_production] ?? 0),
                'traitee' => (float) ($traitees[$type->id_type_systeme_production] ?? 0),
            ]]);
    }

    /**
     * Nombre de demandes par statut (brouillons compris), tous statuts présents.
     *
     * @return array<int, int> valeur de StatutDemandeE => total
     */
    public function parStatut(Campagne $campagne): array
    {
        $comptes = Demande::query()
            ->where('id_campagne', $campagne->id_campagne)
            ->selectRaw(Demande::statutSql().' AS code_statut, COUNT(*) AS total')
            ->groupBy('code_statut')
            ->pluck('total', 'code_statut');

        return collect(StatutDemandeE::cases())
            ->mapWithKeys(fn (StatutDemandeE $s) => [$s->value => (int) ($comptes[$s->value] ?? 0)])
            ->all();
    }

    /**
     * Par appui : ce qui a été demandé, ce qui a été attribué, et le taux.
     * Seules les lignes de demande qui visent un appui précis sont comptées.
     *
     * @return Collection<int, array{appui: string, demandee: float, attribuee: float, taux: ?float}>
     */
    public function satisfactionParAppui(Campagne $campagne): Collection
    {
        // Deux agrégats séparés, puis fusionnés : les joindre ferait multiplier les lignes.
        $demandees = $this->demandesSoumises($campagne)
            ->join('lignes_demandes', 'lignes_demandes.id_demande', '=', 'demandes.id_demande')
            ->whereNotNull('lignes_demandes.id_appui')
            ->groupBy('lignes_demandes.id_appui')
            ->selectRaw('lignes_demandes.id_appui AS id_appui, SUM(lignes_demandes.quantite_demandee) AS total')
            ->pluck('total', 'id_appui');

        $attribuees = $this->demandesSoumises($campagne)
            ->join('attributions', 'attributions.id_demande', '=', 'demandes.id_demande')
            ->join('lignes_attributions', 'lignes_attributions.id_attribution', '=', 'attributions.id_attribution')
            ->groupBy('lignes_attributions.id_appui')
            ->selectRaw('lignes_attributions.id_appui AS id_appui, SUM(lignes_attributions.quantite_attribuee) AS total')
            ->pluck('total', 'id_appui');

        $libelles = DB::table('appuis')
            ->whereIn('id_appui', $demandees->keys()->merge($attribuees->keys())->unique())
            ->pluck('libelle', 'id_appui');

        return $demandees->keys()->merge($attribuees->keys())->unique()
            ->map(function ($idAppui) use ($demandees, $attribuees, $libelles) {
                $demandee = (float) ($demandees[$idAppui] ?? 0);
                $attribuee = (float) ($attribuees[$idAppui] ?? 0);

                return [
                    'appui' => $libelles[$idAppui] ?? "Appui #{$idAppui}",
                    'demandee' => $demandee,
                    'attribuee' => $attribuee,
                    'taux' => $demandee > 0 ? round($attribuee / $demandee * 100, 1) : null,
                ];
            })
            ->sortByDesc('demandee')
            ->values();
    }

    /**
     * Les localités qui demandent le plus (une demande peut couvrir plusieurs localités).
     *
     * @return Collection<string, int> localité => nombre de demandes
     */
    public function parLocalite(Campagne $campagne, int $limite = 10): Collection
    {
        return $this->demandesSoumises($campagne)
            ->join('demandes_localites', 'demandes_localites.id_demande', '=', 'demandes.id_demande')
            ->join('localites', 'localites.id_localite', '=', 'demandes_localites.id_localite')
            ->groupBy('localites.id_localite', 'localites.lib_localite')
            ->orderByDesc('total')
            ->orderBy('localites.lib_localite')
            ->limit($limite)
            ->selectRaw('localites.lib_localite AS localite, COUNT(DISTINCT demandes.id_demande) AS total')
            ->pluck('total', 'localite');
    }

    /**
     * Par type d'appui : demandes qui le sollicitent et attributions qui le servent.
     * Un nombre de dossiers plutôt qu'une quantité : les unités diffèrent d'un appui à l'autre.
     *
     * @return Collection<string, array{demandees: int, servies: int}>
     */
    public function parTypeAppui(Campagne $campagne): Collection
    {
        $demandees = $this->demandesSoumises($campagne)
            ->join('lignes_demandes', 'lignes_demandes.id_demande', '=', 'demandes.id_demande')
            ->join('types_appuis', 'types_appuis.id_type_appui', '=', 'lignes_demandes.id_type_appui')
            ->groupBy('types_appuis.id_type_appui', 'types_appuis.lib_type_appui')
            ->selectRaw('types_appuis.lib_type_appui AS type, COUNT(DISTINCT demandes.id_demande) AS total')
            ->pluck('total', 'type');

        $servies = $this->demandesSoumises($campagne)
            ->join('attributions', 'attributions.id_demande', '=', 'demandes.id_demande')
            ->join('lignes_attributions', 'lignes_attributions.id_attribution', '=', 'attributions.id_attribution')
            ->join('appuis', 'appuis.id_appui', '=', 'lignes_attributions.id_appui')
            ->join('types_appuis', 'types_appuis.id_type_appui', '=', 'appuis.id_type_appui')
            ->groupBy('types_appuis.id_type_appui', 'types_appuis.lib_type_appui')
            ->selectRaw('types_appuis.lib_type_appui AS type, COUNT(DISTINCT attributions.id_attribution) AS total')
            ->pluck('total', 'type');

        return $demandees->keys()->merge($servies->keys())->unique()->sort()->values()
            ->mapWithKeys(fn (string $type) => [$type => [
                'demandees' => (int) ($demandees[$type] ?? 0),
                'servies' => (int) ($servies[$type] ?? 0),
            ]]);
    }

    /**
     * Les bénéficiaires, par type d'acteur : demandes attribuées.
     *
     * @return Collection<string, int> type d'acteur => nombre de demandes attribuées
     */
    public function parTypeActeur(Campagne $campagne): Collection
    {
        return $this->demandesSoumises($campagne)
            ->join('attributions', 'attributions.id_demande', '=', 'demandes.id_demande')
            ->join('types_acteurs', 'types_acteurs.id_type_acteur', '=', 'demandes.id_type_acteur')
            ->groupBy('types_acteurs.id_type_acteur', 'types_acteurs.lib_type_acteur')
            ->orderByDesc('total')
            ->selectRaw('types_acteurs.lib_type_acteur AS type, COUNT(DISTINCT demandes.id_demande) AS total')
            ->pluck('total', 'type');
    }

    /**
     * Moyenne, en jours entiers de calendrier, entre deux colonnes date des
     * lignes qui portent les deux. On compare les jours, pas les heures : une
     * colonne « date » (retrait) n'a pas d'heure à comparer à un « datetime ».
     *
     * @param  Collection<int, object>  $lignes
     */
    private function delaiMoyen(Collection $lignes, string $debut, string $fin): ?float
    {
        $delais = $lignes
            ->filter(fn ($ligne) => $ligne->$debut !== null && $ligne->$fin !== null)
            ->map(fn ($ligne) => (strtotime(substr($ligne->$fin, 0, 10)) - strtotime(substr($ligne->$debut, 0, 10))) / 86400);

        return $delais->isEmpty() ? null : round($delais->avg(), 1);
    }
}
