<?php

namespace App\Domain\Suivi\Services;

use App\Domain\Demande\Enums\StatutDemandeE;
use App\Domain\Demande\Models\Demande;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Suivi\Support\FiltreStatistique;
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
 *
 * Population statistique : les demandes soumises d'UNE campagne, éventuellement
 * restreintes par un filtre (département, localité, type d'appui, type d'acteur,
 * période). Chaque méthode reçoit la campagne et le filtre ; sans filtre, toute
 * la campagne est prise.
 *
 * Les attributions annulées (suppression douce, colonne deleted_at) sont exclues
 * de tous les calculs.
 */
class StatistiqueCampagneService
{
    /** Demandes soumises de la campagne (et du filtre), en requête brute pour joindre à volonté. */
    private function demandesSoumises(Campagne $campagne, ?FiltreStatistique $filtre = null): Builder
    {
        $requete = DB::table('demandes')
            ->where('demandes.id_campagne', $campagne->id_campagne)
            ->whereNotNull('demandes.date_soummission');

        return ($filtre ?? new FiltreStatistique)->appliquer($requete);
    }

    /** Les mêmes demandes, jointes à leur attribution encore valable (non annulée). */
    private function demandesAttribuees(Campagne $campagne, ?FiltreStatistique $filtre = null): Builder
    {
        return $this->demandesSoumises($campagne, $filtre)
            ->join('attributions', 'attributions.id_demande', '=', 'demandes.id_demande')
            ->whereNull('attributions.deleted_at');
    }

    /**
     * Chiffres clés : volumes, taux d'attribution, bénéficiaires, délais.
     *
     * @return array{soumises: int, attribuees: int, rejetees: int, taux_attribution: ?float, beneficiaires: int, delai_arbitrage: ?float, delai_retrait: ?float, superficie_concernee: float, superficie_traitee: float, taux_traitement: ?float}
     */
    public function indicateurs(Campagne $campagne, ?FiltreStatistique $filtre = null): array
    {
        $parStatut = $this->parStatut($campagne, $filtre);

        $soumises = array_sum($parStatut) - $parStatut[StatutDemandeE::brouillon->value];
        $attribuees = $parStatut[StatutDemandeE::recu_attribution->value];

        $attributions = $this->demandesAttribuees($campagne, $filtre)
            ->get(['demandes.id_acteur', 'demandes.date_soummission', 'attributions.created_at', 'attributions.date_destockage']);

        $superficies = $this->superficiesParEcologie($campagne, $filtre);
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
    public function superficiesParEcologie(Campagne $campagne, ?FiltreStatistique $filtre = null): Collection
    {
        $concernees = $this->demandesSoumises($campagne, $filtre)
            ->join('systemes_productions', 'systemes_productions.id_demande', '=', 'demandes.id_demande')
            ->groupBy('systemes_productions.id_type_systeme_production')
            ->selectRaw('systemes_productions.id_type_systeme_production AS id_type, SUM(systemes_productions.superficie) AS total')
            ->pluck('total', 'id_type');

        $traitees = $this->demandesAttribuees($campagne, $filtre)
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
     * Par écologie : le rendement moyen (t/ha) que les DP ont transmis, et combien de superficies
     * déclarées l'ont reçu. Rendement pondéré (production totale ÷ superficie récoltée totale),
     * pas une moyenne de rendements : une grande parcelle pèse plus qu'une petite.
     *
     * @return Collection<string, array{rendement: ?float, production: float, recoltee: float, renseignees: int, declarees: int}>
     */
    public function rendementsParEcologie(Campagne $campagne, ?FiltreStatistique $filtre = null): Collection
    {
        $lignes = $this->demandesSoumises($campagne, $filtre)
            ->join('systemes_productions', 'systemes_productions.id_demande', '=', 'demandes.id_demande')
            ->leftJoin('rendements', 'rendements.id_systeme_production', '=', 'systemes_productions.id_systeme_production')
            ->groupBy('systemes_productions.id_type_systeme_production')
            ->selectRaw('systemes_productions.id_type_systeme_production AS id_type, COUNT(*) AS declarees, COUNT(rendements.id_rendement) AS renseignees, COALESCE(SUM(rendements.production_tonnes), 0) AS production, COALESCE(SUM(rendements.superficie_recoltee), 0) AS recoltee')
            ->get()
            ->keyBy('id_type');

        return DB::table('types_systemes_productions')
            ->orderBy('id_type_systeme_production')
            ->get(['id_type_systeme_production', 'lib_type_systeme_production'])
            ->mapWithKeys(function ($type) use ($lignes) {
                $ligne = $lignes->get($type->id_type_systeme_production);
                $recoltee = (float) ($ligne->recoltee ?? 0);
                $production = (float) ($ligne->production ?? 0);

                return [$type->lib_type_systeme_production => [
                    'rendement' => $recoltee > 0 ? round($production / $recoltee, 2) : null,
                    'production' => $production,
                    'recoltee' => $recoltee,
                    'renseignees' => (int) ($ligne->renseignees ?? 0),
                    'declarees' => (int) ($ligne->declarees ?? 0),
                ]];
            });
    }

    /**
     * Nombre de demandes par statut (brouillons compris), tous statuts présents.
     *
     * @return array<int, int> valeur de StatutDemandeE => total
     */
    public function parStatut(Campagne $campagne, ?FiltreStatistique $filtre = null): array
    {
        $requete = Demande::query()->where('demandes.id_campagne', $campagne->id_campagne);

        $comptes = ($filtre ?? new FiltreStatistique)->appliquer($requete)
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
    public function satisfactionParAppui(Campagne $campagne, ?FiltreStatistique $filtre = null): Collection
    {
        // Deux agrégats séparés, puis fusionnés : les joindre ferait multiplier les lignes.
        $demandees = $this->demandesSoumises($campagne, $filtre)
            ->join('lignes_demandes', 'lignes_demandes.id_demande', '=', 'demandes.id_demande')
            ->whereNotNull('lignes_demandes.id_appui')
            ->groupBy('lignes_demandes.id_appui')
            ->selectRaw('lignes_demandes.id_appui AS id_appui, SUM(lignes_demandes.quantite_demandee) AS total')
            ->pluck('total', 'id_appui');

        $attribuees = $this->demandesAttribuees($campagne, $filtre)
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
    public function parLocalite(Campagne $campagne, ?FiltreStatistique $filtre = null, int $limite = 10): Collection
    {
        return $this->demandesSoumises($campagne, $filtre)
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
    public function parTypeAppui(Campagne $campagne, ?FiltreStatistique $filtre = null): Collection
    {
        $demandees = $this->demandesSoumises($campagne, $filtre)
            ->join('lignes_demandes', 'lignes_demandes.id_demande', '=', 'demandes.id_demande')
            ->join('types_appuis', 'types_appuis.id_type_appui', '=', 'lignes_demandes.id_type_appui')
            ->groupBy('types_appuis.id_type_appui', 'types_appuis.lib_type_appui')
            ->selectRaw('types_appuis.lib_type_appui AS type, COUNT(DISTINCT demandes.id_demande) AS total')
            ->pluck('total', 'type');

        $servies = $this->demandesAttribuees($campagne, $filtre)
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
    public function parTypeActeur(Campagne $campagne, ?FiltreStatistique $filtre = null): Collection
    {
        return $this->demandesAttribuees($campagne, $filtre)
            ->join('types_acteurs', 'types_acteurs.id_type_acteur', '=', 'demandes.id_type_acteur')
            ->groupBy('types_acteurs.id_type_acteur', 'types_acteurs.lib_type_acteur')
            ->orderByDesc('total')
            ->selectRaw('types_acteurs.lib_type_acteur AS type, COUNT(DISTINCT demandes.id_demande) AS total')
            ->pluck('total', 'type');
    }

    /**
     * L'argent de la campagne : ce que valent les attributions (engagé), ce qui est
     * dû une fois les résultats publiés, ce qui est encaissé, et le taux de
     * recouvrement (encaissé ÷ dû).
     *
     * Montant d'une ligne = quantité × coût unitaire × (1 − remise %), comme
     * LigneAttribution::montant(). Les paiements se somment à part : les joindre
     * aux lignes compterait chaque paiement autant de fois qu'il y a de lignes.
     *
     * @return array{engage: float, du: float, encaisse: float, reste: float, taux_recouvrement: ?float}
     */
    public function finances(Campagne $campagne, ?FiltreStatistique $filtre = null): array
    {
        $montantLigne = 'lignes_attributions.quantite_attribuee * COALESCE(lignes_attributions.cout_attribution, 0) * (1 - COALESCE(lignes_attributions.remise, 0) / 100.0)';

        $montants = $this->demandesAttribuees($campagne, $filtre)
            ->join('lignes_attributions', 'lignes_attributions.id_attribution', '=', 'attributions.id_attribution')
            ->selectRaw("COALESCE(SUM({$montantLigne}), 0) AS engage")
            ->selectRaw("COALESCE(SUM(CASE WHEN attributions.date_publication IS NOT NULL THEN {$montantLigne} ELSE 0 END), 0) AS du")
            ->first();

        $encaisse = (float) $this->demandesAttribuees($campagne, $filtre)
            ->whereNotNull('attributions.date_publication')
            ->join('paiements', 'paiements.id_attribution', '=', 'attributions.id_attribution')
            ->sum('paiements.montant');

        $du = round((float) $montants->du, 2);

        return [
            'engage' => round((float) $montants->engage, 2),
            'du' => $du,
            'encaisse' => round($encaisse, 2),
            'reste' => round(max(0, $du - $encaisse), 2),
            'taux_recouvrement' => $du > 0 ? round($encaisse / $du * 100, 1) : null,
        ];
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
