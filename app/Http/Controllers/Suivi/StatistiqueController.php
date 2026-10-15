<?php

namespace App\Http\Controllers\Suivi;

use App\Domain\Demande\Enums\StatutDemandeE;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Suivi\Services\StatistiqueCampagneService;
use App\Domain\Suivi\Support\Graphique;
use App\Http\Controllers\Controller;
use App\Utils\Helper;
use Illuminate\Http\Request;

/**
 * Statistiques d'une campagne (campagne en cours par défaut), pour l'admin.
 */
class StatistiqueController extends Controller
{
    public function index(Request $request, StatistiqueCampagneService $stats)
    {
        $request->validate(['campagne' => 'nullable|integer|exists:campagnes,id_campagne']);

        $campagnes = Campagne::orderByDesc('mois_debut_campagne')->get();
        $campagne = $campagnes->firstWhere('id_campagne', $request->integer('campagne')) ?? Campagne::enCours();

        // Aucune campagne en base : la page l'indique plutôt que de planter.
        if (! $campagne) {
            return view('statistiques.index', ['layout' => Helper::layout(), 'campagne' => null, 'campagnes' => $campagnes]);
        }

        $cas = collect(StatutDemandeE::cases());
        $statuts = $stats->parStatut($campagne);
        $typesAppuis = $stats->parTypeAppui($campagne);
        $localites = $stats->parLocalite($campagne);
        $typesActeurs = $stats->parTypeActeur($campagne);
        $superficies = $stats->superficiesParEcologie($campagne);
        $rendements = $stats->rendementsParEcologie($campagne);

        return view('statistiques.index', [
            'layout' => Helper::layout(),
            'campagne' => $campagne,
            'campagnes' => $campagnes,
            'indicateurs' => $stats->indicateurs($campagne),
            'satisfaction' => $stats->satisfactionParAppui($campagne),
            'graphiqueStatuts' => Graphique::anneau(
                $cas->map->libelle()->all(),
                $cas->map(fn (StatutDemandeE $s) => $statuts[$s->value])->all(),
                $cas->map->couleurGraphique()->all(),
            ),
            'superficies' => $superficies,
            'graphiqueSuperficies' => $superficies->sum(fn ($s) => $s['concernee'] + $s['traitee']) > 0 ? Graphique::barres(
                $superficies->keys()->all(),
                [
                    ['label' => 'Concernée (ha)', 'data' => $superficies->pluck('concernee')->all(), 'color' => '#E5A93B'],
                    ['label' => 'Traitée (ha)', 'data' => $superficies->pluck('traitee')->all(), 'color' => '#1E5E3A'],
                ],
            ) : null,
            'rendements' => $rendements,
            'graphiqueRendements' => $rendements->contains(fn ($r) => $r['rendement'] !== null) ? Graphique::barres(
                $rendements->keys()->all(),
                [['label' => 'Rendement moyen (t/ha)', 'data' => $rendements->map(fn ($r) => $r['rendement'] ?? 0)->values()->all(), 'color' => '#1E5E3A']],
                decimales: true,
            ) : null,
            'graphiqueTypesAppuis' => $typesAppuis->isEmpty() ? null : Graphique::barres(
                $typesAppuis->keys()->all(),
                [
                    ['label' => 'Demandes', 'data' => $typesAppuis->pluck('demandees')->all(), 'color' => '#E5A93B'],
                    ['label' => 'Servies', 'data' => $typesAppuis->pluck('servies')->all(), 'color' => '#1E5E3A'],
                ],
            ),
            'graphiqueLocalites' => $localites->isEmpty() ? null : Graphique::barres(
                $localites->keys()->all(),
                [['label' => 'Demandes', 'data' => $localites->values()->all(), 'color' => '#1E5E3A']],
                horizontal: true,
            ),
            'graphiqueTypesActeurs' => $typesActeurs->isEmpty() ? null : Graphique::anneau(
                $typesActeurs->keys()->all(),
                $typesActeurs->values()->all(),
                ['#1E5E3A', '#E5A93B', '#0ea5e9', '#e11d48', '#8b5cf6'],
            ),
        ]);
    }
}
