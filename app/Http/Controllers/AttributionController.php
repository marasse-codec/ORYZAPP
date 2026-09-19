<?php

namespace App\Http\Controllers;

use App\Enums\PermissionEnum;
use App\Enums\StatutAttributionEnum;
use App\Models\Acteur;
use App\Models\Agent;
use App\Models\Appui;
use App\Models\Attribution;
use App\Models\Campagne;
use App\Models\Demande;
use App\Models\Equipement;
use App\Models\LigneAttribution;
use App\Models\Localite;
use App\Models\MouvementStock;
use App\Models\Service;
use App\Models\SessionCommission;
use App\Models\Traitement;
use App\Models\TypeActeur;
use App\Models\TypeMouvementStock;
use App\Services\SyntheseAttributionService;
use App\Services\SyntheseCommissionService;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttributionController extends Controller
{
    public function index()
    {

        $attributions = Attribution::query()
            ->with(['lignesAttributions.appui', 'demande.acteur', 'session'])
            ->orderByDesc('id_attribution')
            ->userService(Auth::user()->id_service)
            ->get();

        $actions = [];

        if (Auth::user()->can(PermissionEnum::attribuerAppui->value)) {
            $actions = [
                'soumettre' => [
                    'lien' => 'attributions.soumettre',
                    'libelle' => 'Soumettre', // Correction de l'orthographe et ajout d'un libellé
                ],
                'supprimer' => [
                    'lien' => 'attributions.destroy', // Exemple de route de suppression
                    'libelle' => 'Supprimer',
                ],
            ];
        }

        if (Auth::user()->can(PermissionEnum::gererCommission->value)) {
            $actions['soumettre'] = [
                'lien' => 'attributions.valider', // Remplacez par le nom réel de votre route de validation
                'libelle' => 'Valider',
            ];
            $actions['supprimer'] = [
                'lien' => 'attributions.annuler', // Remplacez par le nom réel de votre route d'annulation
                'libelle' => 'Annuler',
            ];
        }

        $attributions->each(function ($a) {
            $a->submissible = $a->id_statut_attribution === StatutAttributionEnum::brouillon;
        });

        $session = SessionCommission::whereNull('date_fin_session')->first();

        $error = $session ? null : "Aucune session active, veuillez en créer une pour traiter les attributions";


        return view('attributions.index', [
            'attributions' => $attributions,
            'layout' => Helper::layout(),
            'sessionOuverte' => $session,
            'actions' => $actions,
        ])->with('error',$error);
    }

    /** Formulaire d'attribution pour une demande donnée. */
    public function create(string $idDemande, SyntheseAttributionService $synthese)
    {
        $service = Service::where('id_service', Auth::user()->id_service)->firstOrFail();
        $demande = Demande::with([
            'acteur',
            'typeActeur',
            'localite',
            'systemesProductions.typeSystemeProduction',
            'lignes' => function ($query) use ($service) {
                $query
                    ->whereHas('typeAppui.service', function ($query) use ($service) {
                        $query->where('id_service', $service->id_service);
                    });
            },
            'attribution.lignesAttributions.appui',
        ])->whereHas('traitements', function ($query) use ($service) {
            $query
                ->where('id_service', $service->id_service)
                ->whereNull('date_fin_traitement');
        })
            ->findOrFail($idDemande);

        // Extraction des IDs des types d'appui de la demande
        $idsTypesAppuis = $demande->typesAppuis()->pluck('id_type_appui');

        return view('attributions.create', [
            'demande' => $demande,
            'catalogue' => Appui::pourUtilisateur()
                    ->whereIn('id_type_appui', $idsTypesAppuis)
                    ->orderBy('libelle')
                    ->get(),            'sessionOuverte' => SessionCommission::whereNull('date_fin_session')->first(),
            'synthese' => $synthese->confrontationPourDemande($demande),
            'layout' => Helper::layout(),
        ]);
    }

    /** Formulaire de création d'une attribution directe / don */
    public function createDirect(SyntheseAttributionService $synthese)
    {
        $service = Service::where('id_service', Auth::user()->id_service)->firstOrFail();

        return view('attributions.create-direct', [
            'acteurs' => Acteur::orderBy('nom')->get(),
            'typesActeurs' => TypeActeur::orderBy('lib_type_acteur')->get(),
            'localites' => Localite::orderBy('lib_localite')->get(),
            'campagnes' => Campagne::orderBy('date_debut_campagne', 'desc')->get(),
            'catalogue' => Appui::pourUtilisateur()->orderBy('libelle')->get(),
            'synthese' => $synthese->confrontationToutsAppuisService($service),
            'layout' => Helper::layout(),
        ]);
    }

    /** Enregistrement d'une attribution directe avec sa demande interne */
    public function storeDirect(Request $request)
    {
        $validated = $request->validate([
            'id_acteur' => 'required|exists:acteurs,id_acteur',
            'id_type_acteur' => 'required|exists:types_acteurs,id_type_acteur',
            'id_localite' => 'required|exists:localites,id_localite',
            'id_campagne' => 'required|exists:campagnes,id_campagne',
            'commentaires' => 'required|string|max:500',
            'remise' => 'nullable|numeric|min:0|max:100',
            'lignes' => 'required|array|min:1',
            'lignes.*.id_appui' => 'required|exists:appuis,id_appui',
            'lignes.*.quantite_attribuee' => 'required|numeric|min:1',
            'lignes.*.cout_attribution' => 'required|numeric|min:0',
            'lignes.*.remise' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated) {
            // 1. Création automatique de la demande interne sous-jacente
            $demande = Demande::create([
                'id_acteur' => $validated['id_acteur'],
                'id_type_acteur' => $validated['id_type_acteur'],
                'id_localite' => $validated['id_localite'],
                'id_campagne' => $validated['id_campagne'],
                'id_user' => Auth::id(),
                'date_demande' => now(),
                'periode_debut_mise_en_place' => now(),
                'periode_fin_mise_en_place' => now(),
                'soumise' => true,
            ]);

            // 2. Associer les types d'appuis aux lignes de la demande
            $appuis = Appui::whereIn('id_appui', collect($validated['lignes'])->pluck('id_appui'))
                ->pluck('id_type_appui')
                ->unique();

            foreach ($appuis as $idTypeAppui) {
                $demande->lignes()->create([
                    'id_type_appui' => $idTypeAppui,
                ]);
            }

            // 3. Création de l'attribution liée
            $attribution = Attribution::create([
                'id_demande' => $demande->id_demande,
                'commentaires' => $validated['commentaires'],
                'remise' => $validated['remise'] ?? 0,
                'id_statut_attribution' => StatutAttributionEnum::brouillon,
            ]);

            // 4. Création des lignes d'attribution
            foreach ($validated['lignes'] as $ligne) {
                $attribution->lignesAttributions()->create([
                    'id_appui' => $ligne['id_appui'],
                    'quantite_attribuee' => $ligne['quantite_attribuee'],
                    'cout_attribution' => $ligne['cout_attribution'],
                    'remise' => $ligne['remise'] ?? 0,
                ]);
            }
        });

        return redirect()->route('attributions.index')
            ->with('success', 'Attribution directe enregistrée avec succès.');
    }

    public function store(Request $request, string $idDemande)
    {

        $service = Service::where('id_service', Auth::user()->id_service)->firstOrFail();

        $demande = Demande::findOrFail($idDemande);

        $valide = $request->validate([
            'commentaires' => 'nullable|string|max:500',
            'lignes' => 'required|array|min:1',
            'lignes.*.id_appui' => 'required|distinct|exists:appuis,id_appui',
            'lignes.*.quantite_attribuee' => 'required|integer|min:1',
            'lignes.*.cout_attribution' => 'nullable|numeric|min:0',
            'lignes.*.remise' => 'nullable|numeric|min:0|max:100',
        ], [
            'lignes.required' => 'Attribuez au moins un appui.',
            'lignes.*.id_appui.distinct' => 'Un même appui ne peut être attribué deux fois.',
        ]);

        // Une demande rejetée ou déjà servie ne se réattribue pas.
        // if (! $demande->estAttribuable()) {
        //     return back()->with('error', 'Cette demande n\'est plus attribuable.');
        // }

        // $session = SessionCommission::findOrFail($valide['id_session']);

        // if (! $session->estOuverte()) {
        //     return back()->with('error', 'La séance de commission est clôturée.');
        // }

        DB::transaction(function () use ($demande, $service, $valide) {

            // 1. Récupérer l'ID du traitement actif pour cette demande et ce service
            $idTraitement = $demande->traitements()
                ->where('id_service', $service->id_service)
                ->whereNull('date_fin_traitement')
                ->value('id_traitement');

            // Sécurité : vérifier qu'un traitement actif existe bien
            if (! $idTraitement) {
                return back()->with('error', 'Aucun traitement actif trouvé pour ce service.');
            }

            // 2. Créer ou récupérer l'attribution rattachée à ce traitement
            $attribution = Attribution::firstOrCreate(
                [
                    'id_demande' => $demande->id_demande,
                    'id_traitement' => $idTraitement,
                ],
                [
                    'id_statut_attribution' => StatutAttributionEnum::brouillon->value,
                ]
            );

            foreach ($valide['lignes'] as $ligne) {
                LigneAttribution::create([
                    'id_attribution' => $attribution->id_attribution,
                    'id_appui' => $ligne['id_appui'],
                    'quantite_attribuee' => $ligne['quantite_attribuee'],
                    'cout_attribution' => $ligne['cout_attribution'] ?? null,
                    'remise' => $ligne['remise'] ?? 0,
                ]);
            }

            $traitement = Traitement::where('id_service', $service->id_service)->where('id_demande', $demande->id_demande)
                ->update([
                    'date_fin_traitement' => now(),
                ]);

            // C'est cette date qui fait basculer la demande en « attribuée ».
            $demande->update(['date_attribution' => now()->toDateString()]);

            $traitementsFini = Traitement::traitementTerminee($demande->id_demande);
            $servicesDemande = $demande->servicesConcernes();

            if ($traitementsFini->count() === $servicesDemande->count()) {
                $demande->traitementTerminee();
            }
        });

        return redirect()
            ->route('demandes.show', $demande->id_demande)
            ->with('success', 'Attribution enregistrée.');
    }

    public function show($idAttribution)
    {
        $attribution = Attribution::findOrFail($idAttribution);

        $attribution->load([
            'demande.acteur',
            'demande.typeActeur',
            'demande.localite',
            'lignesAttributions.appui.uniteAppui',
            'session',
        ]);

        return view('attributions.show', [
            'attribution' => $attribution,
            'layout' => Helper::layout(),
            'sessionOuverte' => SessionCommission::whereNull('date_fin_session')->first(),
            'estValidee' => $attribution->id_statut_attribution === StatutAttributionEnum::validee
        ]);
    }

    public function soumettre(Request $request)
    {
        $valide = $request->validate([
            'id_attribution' => 'required',
        ]);

        $attribution = Attribution::findOrFail($valide['id_attribution']);

        // Une demande doit avoir au moins une ligne
        if ($attribution && $attribution->lignesAttributions()->count() === 0) {
            return redirect()
                ->route('demandes.show', $attribution->id_attribution)
                ->with('error', 'L\'attribution doit contenir au moins une ligne avant sa soumission.');
        }

        DB::transaction(function () use ($attribution) {

            $attribution->update([
                'id_statut_attribution' => StatutAttributionEnum::soumise,
            ]);
        });

        return redirect()
            ->route('attributions.index', $attribution->id_demande)
            ->with('success', 'L\'attribution a été soumise avec succès.');
    }

    public function valider(Request $request)
    {

        $valide = $request->validate([
            'id_attribution' => 'required',
        ]);

        $attribution = Attribution::findOrFail($valide['id_attribution']);
        $sessionOuverte = $request->id_session ? SessionCommission::findOrFail($request->id_session) : SessionCommission::whereNull('date_fin_session')->first();

        $attribution->valider($sessionOuverte->id_session);

        return redirect()
            ->route('attributions.index')
            ->with('success', 'Attribution validée.');
    }

    /**
     * Rejet motivé du dossier par la commission. Le motif est obligatoire :
     * c'est la seule chose que le demandeur recevra en retour.
     */
    public function annuler(Request $request)
    {
        $valide = $request->validate([
            'id_attribution' => 'required',
        ]);

        $attribution = Attribution::findOrFail($valide['id_attribution']);
        $sessionOuverte = $request->id_session ? SessionCommission::findOrFail($request->id_session) : SessionCommission::whereNull('date_fin_session')->first();

        $attribution->annuler($sessionOuverte->id_session);

        return redirect()
            ->route('attributions.index')
            ->with('success', 'Attribution validée.');
    }

    /** Le bon d'attribution, mis en page pour l'impression. */
    public function bon(Attribution $attribution)
    {
        $attribution->load([
            'demande.acteur',
            'demande.typeActeur',
            'demande.localite.departement',
            'session',
            'lignesAttributions.appui.uniteAppui',
        ]);

        return view('attributions.bon', compact('attribution'));
    }

    public function destroy(Attribution $attribution)
    {
        if ($attribution->date_destockage !== null) {
            return back()->with('error', 'Une attribution déjà servie ne peut être retirée.');
        }

        DB::transaction(function () use ($attribution) {
            $attribution->demande?->update(['date_attribution' => null]);
            $attribution->delete();
        });

        return redirect()->route('attributions.index')->with('success', 'Attribution retirée.');
    }

    /**
     * Sortie de stock. C'est le seul endroit où les quantités en stock
     * diminuent : le déstockage et le décrément partent ensemble ou pas du tout.
     */
    public function destocker(Request $request, Attribution $attribution)
    {
        $valide = $request->validate([
            'date_destockage' => 'required|date|before_or_equal:today',
            'recipiendaire' => 'required|string|max:255',
            'numero_piece_identite' => 'nullable|string|max:60',
            'piece_identite' => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'equipements' => 'nullable|array',
            'equipements.*' => 'nullable|array',
        ], [
            'piece_identite.required' => "Scannez la pièce d'identité du récipiendaire.",
            'recipiendaire.required' => 'Indiquez qui se présente pour le retrait.',
        ]);

        // Validation spécifique si des équipements sont requis
        foreach ($attribution->lignesAttributions as $ligne) {
            if ($ligne->appui?->typeAppui?->est_intrant === false) {
                $selectedEquipements = $request->input("equipements.{$ligne->id_ligne_attribution}", []);

                if (count($selectedEquipements) < $ligne->quantite_attribuee) {
                    return back()->withErrors([
                        "equipements.{$ligne->id_ligne_attribution}" => "Veuillez sélectionner exactement {$ligne->quantite_attribuee} équipement(s) pour {$ligne->appui->libelle}.",
                    ])->withInput();
                }
            }
        }

        $chemin = $request->file('piece_identite')
            ->store("retraits/{$attribution->id_attribution}", 'public');

        DB::transaction(function () use ($request, $attribution, $valide, $chemin) {
            $attribution->loadMissing('lignesAttributions.appui.typeAppui');

            $idTypeMouvement = TypeMouvementStock::where('code', 'SORTIE')->value('id_type_mouvement_stock');
            $idAgent = Agent::where('user_id', auth()->id())->value('id_agent') ?? 1;

            foreach ($attribution->lignesAttributions as $ligne) {
                $appui = $ligne->appui;

                // 1. Décrémenter le stock dans tous les cas
                $appui->decrement('quantite_en_stock', $ligne->quantite_attribuee);

                // 2. Récupérer les équipements sélectionnés pour cette ligne
                $equipementIds = $request->input("equipements.{$ligne->id_ligne_attribution}", []);

                // 3. Traitement selon s'il s'agit d'un matériel (non intrant) ou d'un intrant
                if ($appui->typeAppui?->est_intrant === false && ! empty($equipementIds)) {

                    // Créer 1 mouvement de stock par équipement physique
                    foreach ($equipementIds as $equipementId) {
                        MouvementStock::create([
                            'id_appui' => $appui->id_appui,
                            'id_equipement' => $equipementId,
                            'id_type_mouvement_stock' => $idTypeMouvement,
                            'id_agent' => $idAgent,
                            'quantite' => 1,
                            'id_ligne_attribution' => $ligne->id_ligne_attribution,
                            'date_mouvement' => $valide['date_destockage'],
                            'motif' => 'Attribution #'.$attribution->id_attribution,
                        ]);
                    }

                    // Marquer les équipements comme étant en service
                    Equipement::whereIn('id_equipement', $equipementIds)->update([
                        'en_service' => true,
                    ]);

                } else {
                    // Pour un INTRANT (consommable) : 1 mouvement global
                    MouvementStock::create([
                        'id_appui' => $appui->id_appui,
                        'id_equipement' => null,
                        'id_type_mouvement_stock' => $idTypeMouvement,
                        'id_agent' => $idAgent,
                        'quantite' => $ligne->quantite_attribuee,
                        'id_ligne_attribution' => $ligne->id_ligne_attribution,
                        'date_mouvement' => $valide['date_destockage'],
                        'motif' => 'Attribution #'.$attribution->id_attribution,
                    ]);
                }
            }

            // 4. Mettre à jour les informations du bon d'attribution
            $attribution->update([
                'date_destockage' => $valide['date_destockage'],
                'recipiendaire' => $valide['recipiendaire'],
                'numero_piece_identite' => $valide['numero_piece_identite'] ?? null,
                'chemin_piece_identite' => $chemin,
            ]);
        });

        return redirect()->route('recuperations.index')
            ->with('success', 'La sortie de stock a été validée avec succès.');
    }

    public function publier()
    {

        $attributions = Attribution::whereNotNull('id_session')->get();

        foreach ($attributions as $attribution) {
            $attribution->publier();
        }

        return redirect()->route('attributions.index')
            ->with('success', 'Les résultats ont été publiés');
    }
    
}
