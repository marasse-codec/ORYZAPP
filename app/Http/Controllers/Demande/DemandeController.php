<?php

namespace App\Http\Controllers\Demande;

use App\Enums\PermissionEnum;
use App\Enums\StatutDemandeE;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDemandeRequest;
use App\Mail\MailService;
use App\Models\Agent;
use App\Models\Appui;
use App\Models\Campagne;
use App\Models\Demande;
use App\Models\JournalAction;
use App\Models\Localite;
use App\Models\Traitement;
use App\Models\TypeActeur;
use App\Models\TypeAppui;
use App\Models\TypeSystemeProduction;
use App\Models\User;
use App\Notifications\SuiviDossierNotification;
use App\Services\CreationDemandeService;
use App\Services\SyntheseCommissionService;
use App\Utils\Helper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class DemandeController extends Controller
{
    public function index(SyntheseCommissionService $synthese)
    {

        $serviceId = Auth::user()->id_service;

        $demandes = Demande::query()
            ->pourUtilisateur(Auth::user())
            ->with([
                'acteur',
                'typeActeur',
                'localite',
                'attribution',
                'rapports',
                'lignes.typeAppui.service',
                'lignes',
                'traitements',
            ])
            ->latest('id_demande')
            ->get();


        $demandes->each(function ($demande) use ($serviceId) {
            $traitement = $demande->traitements
                ->where('id_service', $serviceId)
                ->first();

            $demande->traitement_terminee = $traitement !== null
                && $traitement->date_fin_traitement !== null;
        });

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
            'localite.departement',
            'systemesProductions.typeSystemeProduction',
            'lignes.typeAppui.service',
            'attribution.lignesAttributions.appui',
            'rapports.service',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('demandes.pdf', compact('demande'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("demande_DEM-{$demande->id_demande}.pdf");
        // Utilisez ->download() à la place de ->stream() si vous voulez forcer le téléchargement direct.
    }

    public function create()
    {
        return view('demandes.create', [
            'typesActeurs' => TypeActeur::with('typesPieces')->orderBy('lib_type_acteur')->get(),
            'localites' => Localite::with('departement')->orderBy('lib_localite')->get(),
            'campagnes' => Campagne::orderByDesc('date_debut_campagne')->limit(2)->get(),
            'typesSystemesProductions' => TypeSystemeProduction::orderBy('lib_type_systeme_production')->get(),
            // Seuls les appuis quantifiables se demandent : une prestation
            // immatérielle n'a ni stock ni quantité à attribuer.

            'typesAppuis' => TypeAppui::orderBy('lib_type_appui')->get(),
            'layout' => Helper::layout(),
        ]);
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

        $servicesIds = $services->pluck('id_service')->flatten()->toArray();


        $users = User::whereIn('id_service',$servicesIds)->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new SuiviDossierNotification(
                titre: 'Nouvelle demande soumise',
                message: "La demande {$demande->id_demande} a été soumise pour traitement.",
                urlAction: route('demandes.show', $demande->id_demande),
                type: 'success'
            ));
        }

        Mail::to('freddegny@gmail.com')->send(new MailService('Message de demande'));

        return redirect()
            ->route('demandes.show', $demande->id_demande)
            ->with('success', 'La demande a été soumise avec succès.');
    }

    public function edit(string $id)
    {
        $demande = Demande::with([
            'acteur',
            'typeActeur',
            'localite.departement',
            'systemesProductions.typeSystemeProduction',
            'lignes.typeAppui',
            'piecesDemandes.typePiece',
            'campagne'
        ])->findOrFail($id);

        return view('demandes.edit', [
            'demande' => $demande,
            'typesActeurs' => TypeActeur::with('typesPieces')->orderBy('lib_type_acteur')->get(),
            'localites' => Localite::with('departement')->orderBy('lib_localite')->get(),
            'typesSystemesProductions' => TypeSystemeProduction::orderBy('lib_type_systeme_production')->get(),
            'campagnes' => Campagne::orderByDesc('date_debut_campagne')->limit(2)->get(),
            'typesAppuis' => TypeAppui::orderBy('lib_type_appui')->get(),
            'layout' => Helper::layout(),
        ]);
    }

    public function update(StoreDemandeRequest $request, string $id)
    {
        // 1. Validation de l'ensemble des données envoyées par le formulaire
        $validated = $request;

        $demande = Demande::with(['acteur', 'lignes', 'systemesProductions'])->findOrFail($id);

        // Règle de garde : Si la demande a déjà été prise en charge, on bloque la modification
        if (! $demande->estModifiable()) {
            return redirect()
                ->route('demandes.show', $demande->id_demande)
                ->with('error', 'Cette demande a déjà fait l\'objet d\'un accusé de réception et ne peut plus être modifiée.');
        }

        DB::transaction(function () use ($request, $validated, $demande) {

            // 2. Mettre à jour les informations de l'acteur associé
            if ($demande->acteur) {
                $demande->acteur->update([
                    'id_type_acteur' => $validated['id_type_acteur'],
                    'nom' => $validated['nom'],
                    'telephone' => $validated['telephone'],
                    'email' => $validated['email'] ?? null,
                ]);
            }

            // 3. Mettre à jour les informations principales de la demande
            $demande->update([
                'id_type_acteur' => $validated['id_type_acteur'],
                'id_localite' => $validated['id_localite'],
                'debut_periode_mise_en_place' => $validated['debut_periode_mise_en_place'],
                'id_campagne' => $validated['id_campagne'],
                'motifs_rejet' => null,
                'date_rejet' => null,
            ]);

            // 4. Mettre à jour les superficies (Gestion de la relation HasMany vers SystemeProduction)
            $demande->systemesProductions()->delete();
            if (!empty($validated['superficies'])) {
                foreach ($validated['superficies'] as $typeSystemeId => $superficie) {
                    if (!is_null($superficie) && $superficie !== '') {
                        $demande->systemesProductions()->create([
                            'id_type_systeme_production' => $typeSystemeId,
                            'superficie' => $superficie
                        ]);
                    }
                }
            }

            // 5. Mettre à jour les lignes d'appuis
            $demande->lignes()->delete();
            foreach ($validated['lignes'] as $ligne) {
                $demande->lignes()->create([
                    'id_appui' => $ligne['id_appui'],
                    'quantite_demandee' => $ligne['quantite_demandee'],
                ]);
            }

            // 6. Gérer l'ajout ou le remplacement des pièces jointes
            if ($request->hasFile('pieces')) {
                foreach ($request->file('pieces') as $idTypePiece => $file) {
                    if ($file->isValid()) {
                        $path = $file->store('pieces_demandes', 'public');

                        $demande->piecesDemandes()->updateOrCreate(
                            ['id_type_piece' => $idTypePiece],
                            ['chemin_fichier' => $path] // Adaptez le nom de la colonne si nécessaire dans votre table pieces_demandes
                        );
                    }
                }
            }

        });

        // 7. Redirection vers la vue de détail avec un message de succès
        return redirect()
            ->route('demandes.show', $demande->id_demande)
            ->with('success', 'La demande n°'.$demande->id_demande.' a été mise à jour avec succès.');
    }

    public function show(string $id)
    {
        $demande = Demande::with([
            'acteur',
            'typeActeur',
            'localite.departement',
            'systemesProductions.typeSystemeProduction',
            'lignes.typeAppui',
            'piecesDemandes.typePiece',
            'rapports.service',
            'attribution.lignesAttributions.appui',
        ])->findOrFail($id);

        return view('demandes.show', [
            'demande' => $demande,
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
            ->with(['acteur', 'localite.departement', 'piecesDemandes.typePiece'])
            ->findOrFail($id);

        return view('demandes.pieces', ['demande' => $demande]);
    }
}
