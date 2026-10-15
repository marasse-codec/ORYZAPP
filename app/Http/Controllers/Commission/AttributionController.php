<?php

namespace App\Http\Controllers\Commission;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Commission\Services\AttributionService;
use App\Domain\Commission\Services\SeanceService;
use App\Domain\Commission\Services\SyntheseAttributionService;
use App\Domain\Demande\Models\Demande;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Services\EnlevementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\EnregistrerAttributionRequest;
use App\Http\Requests\Commission\ModifierAttributionRequest;
use App\Http\Requests\Commission\PublierResultatsRequest;
use App\Http\Requests\Stock\RetirerAttributionRequest;
use App\Utils\Helper;
use Illuminate\Http\Request;

/**
 * Les attributions, côté web : ce contrôleur valide la requête, appelle le cas
 * d'usage du domaine (AttributionService, SeanceService, EnlevementService) et
 * répond. Les règles et les transactions vivent dans le domaine.
 */
class AttributionController extends Controller
{
    /** Les attributions visibles par le rôle (DP : publiées uniquement ; agent de service : son service, en lecture). */
    public function index()
    {
        $attributions = Attribution::query()
            ->with(['lignesAttributions.appui', 'demande.acteur', 'demande.systemesProductions.rendement', 'session'])
            ->orderByDesc('id_attribution')
            ->user()
            ->get();

        return view('attributions.index', [
            'attributions' => $attributions,
            // Le retour de rendement du DP : celles qui l'attendent, celles qui l'ont reçu, le reste.
            'groupes' => [
                'attente' => $attributions->filter(fn ($a) => $a->etatRendement() === 'attente'),
                'renseigne' => $attributions->filter(fn ($a) => $a->etatRendement() === 'renseigne'),
                'autres' => $attributions->filter(fn ($a) => $a->etatRendement() === null),
            ],
            'layout' => Helper::layout(),
        ]);
    }

    /** Formulaire d'attribution pour une demande donnée. */
    public function create(Request $request, string $idDemande, SyntheseAttributionService $synthese)
    {
        $demande = Demande::with([
            'acteur',
            'typeActeur',
            'localites.departement',
            'systemesProductions.typeSystemeProduction',
            'lignes.appui',
        ])->findOrFail($idDemande);

        $idsTypesAppuis = $demande->typesAppuis()->pluck('id_type_appui');

        return view('attributions.create', [
            'demande' => $demande,
            'catalogue' => Appui::pourUtilisateur()
                ->with(['uniteDosage', 'typesSystemesProductions'])
                ->whereIn('id_type_appui', $idsTypesAppuis)
                ->orderBy('libelle')
                ->get(),
            'idSession' => $request->query('idSession'),
            'ecologies' => $this->ecologies($demande),
            'synthese' => $synthese->confrontationPourDemande($demande),
            'layout' => Helper::layout(),
        ]);
    }

    public function store(EnregistrerAttributionRequest $request, string $idDemande, AttributionService $attributions)
    {
        $valide = $request->validated();

        $attribution = $attributions->enregistrer(
            Demande::findOrFail($idDemande),
            $request->query('idSession'),
            $valide['lignes'],
            $valide['commentaires'] ?? null,
            $valide['superficies'] ?? [],
        );

        return redirect()
            ->route('attributions.show', $attribution->id_attribution)
            ->with('success', 'Attribution enregistrée.');
    }

    public function show($idAttribution, Request $request)
    {
        $attribution = Attribution::findOrFail($idAttribution);
        $sessionId = $request->query('idSession');

        $attribution->load([
            'demande.acteur',
            'demande.typeActeur',
            'demande.localites.departement',
            'demande.systemesProductions.typeSystemeProduction',
            'demande.systemesProductions.rendement',
            'lignesAttributions.appui.uniteDosage',
            'session',
            'paiements.encaissePar',
        ]);

        return view('attributions.show', [
            'attribution' => $attribution,
            'layout' => Helper::layout(),
            'sessionOuverte' => $sessionId ? SessionCommission::where('id_session', $sessionId)->first() : null,
        ]);
    }

    /** Formulaire de modification d'une attribution : seulement tant que sa séance est ouverte. */
    public function edit(string $idAttribution, SyntheseAttributionService $synthese)
    {
        $attribution = Attribution::with([
            'demande.lignes.typeAppui',
            'demande.systemesProductions.typeSystemeProduction',
            'demande.localites',
            'superficies',
            'lignesAttributions.appui.typeAppui',
        ])->findOrFail($idAttribution);

        if (! $attribution->estModifiable()) {
            return redirect()
                ->route('attributions.show', $attribution->id_attribution)
                ->with('error', 'La séance est clôturée : cette attribution est validée et ne peut plus être modifiée.');
        }

        return view('attributions.edit', [
            'attribution' => $attribution,
            'demande' => $attribution->demande,
            'ecologies' => $this->ecologies($attribution->demande, $attribution),
            'layout' => Helper::layout(),
        ] + $synthese->pourEdition($attribution, auth()->user()->service));
    }

    public function update(ModifierAttributionRequest $request, string $idAttribution, AttributionService $attributions)
    {
        $attribution = Attribution::findOrFail($idAttribution);
        $valide = $request->validated();

        $attributions->modifier($attribution, $valide['lignes'], $valide['commentaires'] ?? null, $valide['superficies'] ?? []);

        return redirect()
            ->route('attributions.show', $attribution->id_attribution)
            ->with('success', 'Attribution mise à jour avec succès.');
    }

    public function destroy(Attribution $attribution, AttributionService $attributions)
    {
        $attributions->annuler($attribution);

        return redirect()->route('attributions.index')->with('success', 'Attribution retirée.');
    }

    /** Le bon d'attribution, mis en page pour l'impression. */
    public function bon(Attribution $attribution)
    {
        abort_unless($attribution->estImprimable(), 403, "Le bon n'est disponible qu'une fois les résultats de la séance publiés.");

        $attribution->load([
            'demande.acteur',
            'demande.typeActeur',
            'demande.localites.departement',
            'session',
            'lignesAttributions.appui.uniteDosage',
        ]);

        return view('attributions.bon', compact('attribution'));
    }

    /** Le courrier notifiant l'attribution au demandeur, mis en page pour l'impression. */
    public function courrier(Attribution $attribution)
    {
        abort_unless($attribution->estImprimable(), 403, "Le courrier n'est disponible qu'une fois les résultats de la séance publiés.");

        $attribution->load([
            'demande.acteur',
            'demande.typeActeur',
            'demande.localites.departement',
            'session',
            'lignesAttributions.appui.uniteDosage',
        ]);

        return view('attributions.courrier', compact('attribution'));
    }

    /**
     * Les écologies de la demande, avec la superficie demandée et celle que la commission peut traiter
     * (celle déjà enregistrée en modification, sinon la superficie demandée).
     *
     * @return array<int, array{id_type: int, libelle: string, demandee: float, traitee: float}>
     */
    private function ecologies(Demande $demande, ?Attribution $attribution = null): array
    {
        $demande->loadMissing('systemesProductions.typeSystemeProduction');
        $traitees = $attribution?->superficies->pluck('superficie_traitee', 'id_type_systeme_production');

        return $demande->systemesProductions->map(fn ($systeme) => [
            'id_type' => $systeme->id_type_systeme_production,
            'libelle' => $systeme->typeSystemeProduction?->lib_type_systeme_production,
            'demandee' => (float) $systeme->superficie,
            // En modification, une écologie sans ligne a été ramenée à 0 par la commission.
            'traitee' => (float) ($attribution ? ($traitees[$systeme->id_type_systeme_production] ?? 0) : $systeme->superficie),
        ])->values()->all();
    }

    /** Sortie de stock : on sert un bon publié (cf. EnlevementService). */
    public function destocker(RetirerAttributionRequest $request, Attribution $attribution, EnlevementService $retraits)
    {
        $retraits->servir(
            $attribution,
            $request->validated(),
            $request->file('piece_identite'),
            $request->user(),
            $request->input('equipements', []),
        );

        return redirect()->route('enlevements.index')
            ->with('success', 'La sortie de stock a été validée avec succès.');
    }

    /** Publication des résultats d'une séance clôturée. */
    public function publier(PublierResultatsRequest $request, SeanceService $seances)
    {
        $idSession = $request->validated('id_session');

        $seances->publier(SessionCommission::findOrFail($idSession));

        return redirect()->route('commissions.show', $idSession)
            ->with('success', 'Les résultats ont été publiés');
    }
}
