<?php

namespace App\Http\Controllers\Suivi;

use App\Domain\Demande\Enums\StatutDemandeE;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Referentiel\Models\Departement;
use App\Domain\Referentiel\Models\Localite;
use App\Domain\Referentiel\Models\TypeActeur;
use App\Domain\Referentiel\Models\TypeAppui;
use App\Domain\Suivi\Services\ComparaisonCampagnesService;
use App\Domain\Suivi\Services\StatistiqueCampagneService;
use App\Domain\Suivi\Support\FiltreStatistique;
use App\Domain\Suivi\Support\Graphique;
use App\Http\Controllers\Controller;
use App\Http\Requests\Suivi\StatistiqueRequest;
use App\Utils\Helper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

/**
 * Statistiques : une campagne (la campagne en cours par défaut) avec ses filtres,
 * la comparaison entre campagnes, et le rapport de campagne en PDF.
 */
class StatistiqueController extends Controller
{
    public function index(StatistiqueRequest $request, StatistiqueCampagneService $stats)
    {
        $campagne = $request->campagne();
        $choixCycle = $this->choixCycle($request->annee($campagne));

        // Aucune campagne en base : la page l'indique plutôt que de planter.
        if (! $campagne) {
            return view('statistiques.index', ['layout' => Helper::layout(), 'campagne' => null] + $choixCycle + $this->listes());
        }

        $filtre = $request->filtre();
        $donnees = $this->donnees($stats, $campagne, $filtre);

        $cas = collect(StatutDemandeE::cases());
        $statuts = $stats->parStatut($campagne, $filtre);
        $typesAppuis = $stats->parTypeAppui($campagne, $filtre);
        $localites = $stats->parLocalite($campagne, $filtre);
        $typesActeurs = $stats->parTypeActeur($campagne, $filtre);
        $superficies = $donnees['superficies'];
        $rendements = $donnees['rendements'];

        return view('statistiques.index', $donnees + $choixCycle + $this->listes() + [
            'layout' => Helper::layout(),
            'campagne' => $campagne,
            'filtre' => $filtre,
            'graphiqueStatuts' => Graphique::anneau(
                $cas->map->libelle()->all(),
                $cas->map(fn (StatutDemandeE $s) => $statuts[$s->value])->all(),
                $cas->map->couleurGraphique()->all(),
            ),
            'graphiqueSuperficies' => $superficies->sum(fn ($s) => $s['concernee'] + $s['traitee']) > 0 ? Graphique::barres(
                $superficies->keys()->all(),
                [
                    ['label' => 'Concernée (ha)', 'data' => $superficies->pluck('concernee')->all(), 'color' => '#E5A93B'],
                    ['label' => 'Traitée (ha)', 'data' => $superficies->pluck('traitee')->all(), 'color' => '#1E5E3A'],
                ],
            ) : null,
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

    /**
     * Comparaison entre campagnes : par défaut, les cinq plus récentes ; on peut en
     * cocher d'autres. Les filtres s'appliquent à toutes les campagnes comparées.
     */
    public function comparaison(StatistiqueRequest $request, ComparaisonCampagnesService $comparaison)
    {
        $filtre = $request->filtre();

        // Les cycles cochés ; sinon tous les cycles de l'année choisie ; sinon les cinq plus récents déjà commencés.
        $choisies = match (true) {
            $request->filled('campagnes') => Campagne::whereIn('id_campagne', $request->input('campagnes'))->get(),
            $request->filled('annee') => Campagne::deLAnnee($request->integer('annee'))->get(),
            default => Campagne::commencees()->orderByDesc('mois_debut_campagne')->limit(5)->get(),
        };

        $lignes = $comparaison->comparer($choisies, $filtre);
        $libelles = $lignes->map(fn ($ligne) => $ligne['campagne']->libelleComplet())->all();

        return view('statistiques.comparaison', $this->listes() + [
            'layout' => Helper::layout(),
            'filtre' => $filtre,
            'choisies' => $choisies->pluck('id_campagne')->all(),
            'anneeChoisie' => $request->filled('annee') ? $request->integer('annee') : null,
            'lignes' => $lignes,
            'indicateurs' => ComparaisonCampagnesService::INDICATEURS,
            // Un graphique par famille d'indicateurs : les unités ne se mélangent pas sur un même axe.
            'graphiqueVolumes' => $this->courbes($lignes, $libelles, ['soumises', 'beneficiaires']),
            'graphiqueTaux' => $this->courbes($lignes, $libelles, ['taux_attribution', 'taux_traitement', 'taux_recouvrement']),
        ]);
    }

    /** Le rapport de campagne, à imprimer ou à transmettre (PDF). */
    public function rapport(StatistiqueRequest $request, StatistiqueCampagneService $stats)
    {
        $campagne = $request->campagne() ?? abort(404, 'Aucune campagne enregistrée.');
        $filtre = $request->filtre();

        JournalAction::consigner(ActionJournalEnum::exportTelecharge, $campagne, ['document' => 'rapport de campagne'] + $filtre->parametres());

        $pdf = Pdf::loadView('statistiques.rapport', $this->donnees($stats, $campagne, $filtre) + [
            'campagne' => $campagne,
            'filtre' => $filtre,
            'descriptionFiltre' => $this->descriptionFiltre($filtre),
            'parLocalite' => $stats->parLocalite($campagne, $filtre),
            'parTypeActeur' => $stats->parTypeActeur($campagne, $filtre),
        ])->setPaper('a4');

        return $pdf->download('rapport-campagne-'.str($campagne->libelleComplet())->slug().'.pdf');
    }

    /** Les chiffres partagés par la page et le rapport. */
    private function donnees(StatistiqueCampagneService $stats, Campagne $campagne, FiltreStatistique $filtre): array
    {
        return [
            'indicateurs' => $stats->indicateurs($campagne, $filtre),
            'finances' => $stats->finances($campagne, $filtre),
            'satisfaction' => $stats->satisfactionParAppui($campagne, $filtre),
            'superficies' => $stats->superficiesParEcologie($campagne, $filtre),
            'rendements' => $stats->rendementsParEcologie($campagne, $filtre),
        ];
    }

    /** Le choix en deux temps : l'année, puis l'un de ses cycles. */
    private function choixCycle(?int $annee): array
    {
        return [
            'annees' => Campagne::annees(),
            'annee' => $annee,
            'cycles' => $annee ? Campagne::deLAnnee($annee)->get() : collect(),
        ];
    }

    /** Les listes des menus déroulants de filtre. */
    private function listes(): array
    {
        return [
            'annees' => Campagne::annees(),
            'campagnes' => Campagne::orderByDesc('mois_debut_campagne')->get(),
            'departements' => Departement::orderBy('lib_departement')->get(),
            'localites' => Localite::orderBy('lib_localite')->get(),
            'typesAppuis' => TypeAppui::orderBy('lib_type_appui')->get(),
            'typesActeurs' => TypeActeur::orderBy('lib_type_acteur')->get(),
        ];
    }

    /** Les filtres actifs en clair, pour l'en-tête du rapport. */
    private function descriptionFiltre(FiltreStatistique $filtre): string
    {
        $morceaux = array_filter([
            $filtre->idDepartement ? 'Département : '.Departement::find($filtre->idDepartement)?->lib_departement : null,
            $filtre->idLocalite ? 'Localité : '.Localite::find($filtre->idLocalite)?->lib_localite : null,
            $filtre->idTypeAppui ? "Type d'appui : ".TypeAppui::find($filtre->idTypeAppui)?->lib_type_appui : null,
            $filtre->idTypeActeur ? "Type d'acteur : ".TypeActeur::find($filtre->idTypeActeur)?->lib_type_acteur : null,
            $filtre->du ? 'Soumises à partir du '.$filtre->du->format('d/m/Y') : null,
            $filtre->au ? "Soumises jusqu'au ".$filtre->au->format('d/m/Y') : null,
        ]);

        return $morceaux === [] ? 'Toute la campagne' : implode(' · ', $morceaux);
    }

    /** Un graphique en courbes : une courbe par indicateur, un point par campagne. */
    private function courbes(Collection $lignes, array $libelles, array $cles): ?array
    {
        if ($lignes->count() < 2) {
            return null;
        }

        $couleurs = ['#1E5E3A', '#E5A93B', '#0ea5e9'];

        return Graphique::courbes($libelles, collect($cles)->values()->map(fn (string $cle, int $i) => [
            'label' => ComparaisonCampagnesService::INDICATEURS[$cle][0],
            'data' => $lignes->map(fn ($ligne) => $ligne['valeurs'][$cle])->all(),
            'color' => $couleurs[$i % count($couleurs)],
        ])->all());
    }
}
