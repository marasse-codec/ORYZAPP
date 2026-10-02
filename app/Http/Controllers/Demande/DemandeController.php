<?php

namespace App\Http\Controllers\Demande;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\DemandeMarquerVisiteRequest;
use App\Http\Requests\StoreDemandeRequest;
use App\Http\Requests\UpdateDemandeRequest;
use App\Models\Campagne;
use App\Models\Demande;
use App\Models\Localite;
use App\Models\Traitement;
use App\Models\TypeActeur;
use App\Models\TypeAppui;
use App\Models\TypeSystemeProduction;
use App\Services\CreationDemandeService;
use App\Services\SyntheseCommissionService;
use App\Services\UpdateDemandeService;
use App\Utils\Helper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DemandeController extends Controller
{
    public function index(SyntheseCommissionService $synthese)
{
    $serviceId = Auth::user()->id_service;

    $demandes = Demande::query()
    ->pourUtilisateur(Auth::user())
    ->attribuables()
    ->with([
        'acteur',
        'typeActeur',
        'localites.departement',
        'systemesProductions.typeSystemeProduction',
        'user',
        'lignes',
        'traitements' => fn ($q) => $q->where('id_service', $serviceId)->get(),
        'attribution'
    ])
    ->latest('id_demande')
    ->get();

        // La confrontation avec les stocks ne s'affiche que pour qui arbitre :
        // ailleurs, l'état des stocks n'a pas à transparaître.
        $peutArbitrer = Auth::user()->can(PermissionEnum::gererCommission->value);
        $dossiers = $peutArbitrer ? $synthese->dossiersEnAttente() : collect();

        return view('demandes.index', [
            'demandes' => $demandes,
            'dossiersCommission' => $dossiers,
            'confrontation' => $peutArbitrer ? $synthese->confrontation($dossiers) : collect(),
            'layout' => Helper::layout(),
        ]);
    }

    public function telechargerPdf($id)
    {
        $demande = Demande::with([
            'acteur',
            'typeActeur',
            'localites.departement', // Relation Many-to-Many corrigée
            'systemesProductions.typeSystemeProduction',
            'lignes.typeAppui.service',
            'attributions.lignesAttributions.appui',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('demandes.pdf', compact('demande'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("demande_DEM-{$demande->id_demande}.pdf");
    }

    public function create()
    {
        // 1. Récupération des modèles avec leurs relations
        $typesActeurs = TypeActeur::with('typesPieces')->get();
        $typesAppuis = TypeAppui::with(['service', 'appuis.uniteDosage'])->get();
        $localites = Localite::with('departement')->get();
        $campagnes = Campagne::all();
        $typesSystemesProductions = TypeSystemeProduction::all();

        // 2. Préparation des structures JSON/Arrays pour Alpine.js
        $piecesParTypeActeur = $typesActeurs->mapWithKeys(fn ($type) => [
            $type->id_type_acteur => $type->typesPieces->map(fn ($piece) => [
                'id' => $piece->id_type_piece,
                'libelle' => $piece->lib_type_piece,
                'obligatoire' => (bool) $piece->pivot->est_obligatoire,
            ])->values(),
        ]);

        $typesActeursMeta = $typesActeurs->mapWithKeys(fn ($type) => [
            $type->id_type_acteur => [
                'libelle' => $type->lib_type_acteur,
                'est_structure' => !in_array(
                    mb_strtolower(trim($type->lib_type_acteur)), 
                    ['individu', 'groupement non formel', 'producteur individuel']
                )
            ]
        ]);

        $appuisDisponibles = $typesAppuis->flatMap(function($type) {
            return $type->appuis->map(fn($appui) => [
                'id' => $appui->id_appui,
                'libelle' => $appui->libelle,
                'id_type_appui' => $appui->id_type_appui,
                'unite' => $appui->uniteDosage?->lib_unite ?? '',
                'estEquipement' => $appui->estEquipement()
            ]);
        })->values();

        $layout = Helper::layout();

        return view('demandes.create', compact(
            'typesActeurs',
            'typesAppuis',
            'localites',
            'campagnes',
            'typesSystemesProductions',
            'piecesParTypeActeur',
            'typesActeursMeta',
            'appuisDisponibles',
            'layout'
        ));
    }

    public function store(StoreDemandeRequest $request, CreationDemandeService $service)
    {
        $demande = $service->creer($request->validated(), $request->file('pieces', []));

        return redirect()
            ->route('demandes.show', $demande->id_demande)
            ->with('success', 'Demande enregistrée avec succès.');
    }

    public function soumettre(string $id)
    {
        $demande = Demande::findOrFail($id);

        // Une demande déjà soumise ne peut pas être soumise à nouveau
        if (!$demande->estModifiable()) {
            return redirect()
                ->route('demandes.index')
                ->with('error', 'Cette demande ne peut plus être soumise.');
        }

        // Une demande doit avoir au moins une ligne
        if ($demande->lignes()->count() === 0) {
            return redirect()
                ->route('demandes.show', $demande->id_demande)
                ->with('error', 'La demande doit contenir au moins un appui avant sa soumission.');
        }

        /** @var Collection $services */
        $services = $demande->getServices();

        DB::transaction(function () use ($demande,$services) {

            $demande->soumettre();
            
            Traitement::creerTraitements($demande->id_demande, $services);

        });

        return redirect()
            ->route('demandes.show', $demande->id_demande)
            ->with('success', 'La demande a été soumise avec succès.');
    }

    public function edit(string $id)
    {
        // 1. Chargement de la demande et de ses relations
        $demande = Demande::with([
            'acteur',
            'typeActeur',
            'localites.departement',
            'systemesProductions.typeSystemeProduction',
            'lignes.typeAppui.service',
            'lignes.appui.uniteDosage',
            'piecesDemandes.typePiece',
            'campagne'
        ])->findOrFail($id);

        // 2. Collections de référence pour le formulaire
        $typesActeurs = TypeActeur::with('typesPieces')->orderBy('lib_type_acteur')->get();
        $localites = Localite::with('departement')->orderBy('lib_localite')->get();
        $typesSystemesProductions = TypeSystemeProduction::orderBy('lib_type_systeme_production')->get();
        $campagnes = Campagne::orderByDesc('mois_debut_campagne')->get();
        $typesAppuis = TypeAppui::with(['service', 'appuis.uniteDosage'])->orderBy('lib_type_appui')->get();

        // -----------------------------------------------------------------
        // MAPPINGS & PRÉPARATION DES DONNÉES POUR JS
        // -----------------------------------------------------------------

        // A. Matrice des pièces requises par type d'acteur
        $piecesParTypeActeur = $typesActeurs->mapWithKeys(fn ($type) => [
            $type->id_type_acteur => $type->typesPieces->map(fn ($piece) => [
                'id' => $piece->id_type_piece,
                'libelle' => $piece->lib_type_piece,
                'obligatoire' => (bool) $piece->pivot->est_obligatoire,
            ])->values(),
        ]);

        // B. Métadonnées des types d'acteurs (structure / individu)
        $typesActeursMeta = $typesActeurs->mapWithKeys(fn ($type) => [
            $type->id_type_acteur => [
                'libelle' => $type->lib_type_acteur,
                'est_structure' => !in_array(
                    mb_strtolower(trim($type->lib_type_acteur)), 
                    ['individu', 'groupement non formel', 'producteur individuel']
                )
            ]
        ]);

        // C. Catalogue complet des appuis avec l'unité de mesure
        $appuisDisponibles = $typesAppuis->flatMap(function ($type) {
            return $type->appuis->map(fn ($appui) => [
                'id' => $appui->id_appui,
                'libelle' => $appui->libelle,
                'id_type_appui' => $appui->id_type_appui,
                'unite' => $appui->uniteDosage?->lib_unite ?? ''
            ]);
        })->values();

        // D. Extraction des lignes d'appuis existantes (avec gestion old())
        $lignesDB = $demande->lignes->map(function ($ligne) {
            return [
                'id_type_appui' => (string) ($ligne->id_type_appui ?? $ligne->appui?->id_type_appui ?? ''),
                'id_appui'      => (string) ($ligne->id_appui ?? ''),
                'quantite'      => $ligne->quantite_demandee ?? '',
            ];
        })->toArray();

        $lignesInitiales = old('lignes', empty($lignesDB) ? [['id_type_appui' => '', 'id_appui' => '', 'quantite' => '']] : $lignesDB);

        // E. Superficies existantes ventilées par système de production
        $superficiesDB = [];
        foreach ($typesSystemesProductions as $sysType) {
            $id = $sysType->id_type_systeme_production;
            $sysExistant = $demande->systemesProductions->firstWhere('id_type_systeme_production', $id);
            $valeurBdd = $sysExistant ? (float) $sysExistant->superficie : '';
            $superficiesDB[$id] = $valeurBdd;
        }
        $superficiesInitiales = old('superficies', $superficiesDB);

        // F. Premier département pour l'onglet localités
        $premierDepartementId = $demande->localites->first()?->id_departement ?? '';

        // 3. Transmission des données à la vue
        return view('demandes.edit', [
            'demande'                  => $demande,
            'typesActeurs'             => $typesActeurs,
            'localites'                => $localites,
            'typesSystemesProductions' => $typesSystemesProductions,
            'campagnes'                => $campagnes,
            'typesAppuis'              => $typesAppuis,
            'layout'                   => Helper::layout(),

            // Variables JS préparées
            'piecesParTypeActeur'      => $piecesParTypeActeur,
            'typesActeursMeta'         => $typesActeursMeta,
            'appuisDisponibles'        => $appuisDisponibles,
            'lignesInitiales'          => array_values($lignesInitiales),
            'superficiesInitiales'     => $superficiesInitiales,
            'premierDepartementId'     => $premierDepartementId,
        ]);
    }

   public function update(UpdateDemandeRequest $request, string $id, UpdateDemandeService $updateDemandeService)
    {
        $demande = Demande::with(['acteur', 'lignes', 'systemesProductions'])->findOrFail($id);

        // Règle de garde : Si la demande a déjà été prise en charge
        if (! $demande->estModifiable()) {
            return redirect()
                ->route('demandes.show', $demande->id_demande)
                ->with('error', 'Cette demande a déjà fait l\'objet d\'un traitement et ne peut plus être modifiée.');
        }

        // Récupération des données validées et des fichiers envoyés
        $validatedData = $request->validated();
        $uploadedFiles = $request->file('pieces', []);

        // Exécution du service de mise à jour
        $updateDemandeService->execute($demande, $validatedData, $uploadedFiles);

        return redirect()
            ->route('demandes.show', $demande->id_demande)
            ->with('success', 'La demande n°' . $demande->id_demande . ' a été mise à jour avec succès.');
    }
    public function show(string $id)
    {
        $demande = Demande::with([
            'acteur',
            'sessionCommission',
            'typeActeur',
            'localites.departement',
            'systemesProductions.typeSystemeProduction',
            'lignes.typeAppui',
            'piecesDemandes.typePiece',
            'attributions',
            'attributions.lignesAttributions',
        ])->findOrFail($id);

        return view('demandes.show', [
            'demande' => $demande,
            'sessionFinie' => $demande->sessionCommission?->estFinie() ?? false,
            'layout' => Helper::layout(),
        ]);
    }

    /**
     * Les pièces jointes sur papier. Le dossier physique circule encore en
     * commission et en visite de terrain : il doit pouvoir se reconstituer
     * en une seule impression, bordereau en tête.
     */
    public function pieces(string $id)
    {
        $demande = Demande::query()
            ->pourUtilisateur(Auth::user())
            ->with(['acteur', 'localites.departement', 'piecesDemandes.typePiece'])
            ->findOrFail($id);

        return view('demandes.pieces', ['demande' => $demande]);
    }

    public function visiter(DemandeMarquerVisiteRequest $request)
    {
        $validatedData = $request->validated();

        $demande = Demande::findOrFail($validatedData['id_demande']);

        $demande->marquerVisite($validatedData['observations']);

        return redirect()
            ->route('demandes.index')
            ->with('success', 'La demande n°' . $demande->id_demande . ' a été mise à jour avec succès.');

    }
}
