<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Enums\StatutDemandeE;
use App\Models\Demande;
use App\Models\DemandeCorrection;
use App\Models\LigneDemande;
use App\Models\Rapport;
use App\Models\Service;
use App\Models\Traitement;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * L'instruction d'une demande par les services fournisseurs. Chaque service
 * concerné — déduit des appuis sollicités via leur type — rend un rapport ;
 * la demande ne part en commission qu'une fois tous les accords réunis.
 */
class InstructionController extends Controller
{
    /** Les dossiers qui relèvent du service de l'agent connecté. */
    public function index()
    {
        $service = $this->serviceDeLAgent();

        $demandes = Demande::query()
            ->where('id_statut_demande', StatutDemandeE::soumise->value)
            ->whereHas(
                'lignes.appui.typeAppui',
                fn ($q) => $q->where('id_service', $service->id_service)
            )
            ->with([
                'acteur',
                'typeActeur',
                'localite',
                'rapports',
                'lignes.appui.typeAppui.service',
            ])
            ->latest('id_demande')
            ->get();

        return view('instructions.index', [
            'service' => $service,
            'demandes' => $demandes,
            'servicesDisponibles' => $this->servicesDisponibles(),
            'layout' => Helper::layout(),
        ]);
    }

    /** L'écran d'instruction : le dossier complet, réduit à la part du service. */
    public function show(string $idDemande)
    {
        $service = $this->serviceDeLAgent();
        $demande = $this->demandeDuService($idDemande, $service);

        return view('instructions.show', [
            'service' => $service,
            'demande' => $demande,
            'rapport' => $demande->rapports->firstWhere('id_service', $service->id_service),
            'lignesDuService' => $this->lignesDuService($demande, $service),
            'layout' => Helper::layout(),
        ]);
    }

    /**
     * La fiche d'instruction sur papier : le dossier résumé, avec des colonnes
     * et un cadre laissés blancs pour la visite de terrain, où l'agent écrit
     * au stylo avant de reporter ses constats dans la plateforme.
     */
    public function fiche(string $idDemande)
    {
        $service = $this->serviceDeLAgent();

        return view('instructions.fiche', $this->donneesFiche(
            $this->demandeDuService($idDemande, $service),
            $service
        ));
    }

    /**
     * La liasse : les fiches de plusieurs dossiers en un seul tirage. Une
     * tournée de terrain couvre rarement un dossier isolé, et sortir les
     * fiches une à une coûte autant de passages par l'imprimante.
     */
    public function fiches(Request $request)
    {
        $service = $this->serviceDeLAgent();

        $valide = $request->validate([
            'dossiers' => ['required', 'array', 'min:1'],
            'dossiers.*' => ['integer'],
        ]);

        // Chaque dossier repasse par le contrôle d'accès : une liasse ne doit
        // pas devenir le moyen de tirer la fiche d'un service voisin.
        $fiches = collect($valide['dossiers'])
            ->unique()
            ->map(fn ($id) => $this->donneesFiche(
                $this->demandeDuService((string) $id, $service),
                $service
            ));

        return view('instructions.fiches', [
            'service' => $service,
            'fiches' => $fiches,
        ]);
    }

    /** Ce qu'une fiche affiche, qu'elle soit seule ou prise dans une liasse. */
    private function donneesFiche(Demande $demande, Service $service): array
    {
        $lignesDuService = $this->lignesDuService($demande, $service);

        return [
            'service' => $service,
            'demande' => $demande,
            'lignesDuService' => $lignesDuService,
            'rapport' => $demande->rapports->firstWhere('id_service', $service->id_service),
            'autresServices' => $demande->lignes->diff($lignesDuService)
                ->map(fn ($ligne) => $ligne->appui?->typeAppui?->service?->lib_service)
                ->filter()->unique()->values(),
        ];
    }

    /**
     * Enregistre le rapport du service. Un accord vaut autorisation de passer
     * en commission ; sans accord, l'observation est conservée mais le dossier
     * reste en instruction.
     */
    public function store(Request $request, string $idDemande)
    {
        $service = $this->serviceDeLAgent();
        $demande = $this->demandeDuService($idDemande, $service);

        $valide = $request->validate([
            'commentaire' => 'nullable|string|max:2000',
            'accorder' => 'nullable|boolean',
        ]);

        $accorde = (bool) ($valide['accorder'] ?? false);

        Rapport::updateOrCreate(
            ['id_demande' => $demande->id_demande, 'id_service' => $service->id_service],
            [
                'commentaire' => $valide['commentaire'] ?? null,
                'date_rapport' => now()->toDateString(),
                'accord_service' => $accorde ? now()->toDateString() : null,
            ]
        );

        return redirect()->route('instructions.index')->with(
            'success',
            $accorde
                ? 'Dossier autorisé pour la commission au titre du '.$service->lib_service.'.'
                : 'Observation enregistrée : le dossier reste en instruction.'
        );
    }

    /**
     * Le service au titre duquel on instruit. Un agent a le sien ; un
     * administrateur, qui n'est rattaché à aucun service, choisit celui dont
     * il endosse le rôle le temps de la consultation.
     */
    private function serviceDeLAgent(): Service
    {
        if ($service = Auth::user()->service) {
            return $service;
        }

        abort_unless(Auth::user()->hasRole(RoleEnum::admin->value), 403,
            "Votre compte n'est rattaché à aucun service.");

        $demande = request()->query('service');

        return $demande
            ? Service::findOrFail($demande)
            : Service::orderBy('id_service')->firstOrFail();
    }

    /** Les services entre lesquels l'administrateur peut basculer. */
    private function servicesDisponibles()
    {
        return Auth::user()->service
            ? collect()
            : Service::orderBy('lib_service')->get();
    }

    /** Un agent n'ouvre que les dossiers qui sollicitent un appui de son service. */
    private function demandeDuService(string $idDemande, Service $service): Demande
    {
        $demande = Demande::with([
            'acteur',
            'typeActeur',
            'localite.departement',
            'systemesProduction.typeSystemeProduction',
            'lignes.appui.uniteAppui',
            'piecesDemandes.typePiece',
            'rapports.service',
        ])->findOrFail($idDemande);

        abort_if($this->lignesDuService($demande, $service)->isEmpty(), 403, 'Ce dossier ne concerne pas votre service.');

        return $demande;
    }

    /**
     * Accuse réception / prend en charge en masse tous les dossiers
     * actuellement listés pour le service connecté.
     */
    public function prendreEnChargeTous(Request $request)
    {
        $service = $this->serviceDeLAgent();

        $demandes = Demande::query()
            ->whereNull('date_rejet')
            ->whereNull('date_attribution')
            ->where('id_statut_demande', StatutDemandeE::soumise->value)
            ->whereHas(
                'lignes.appui.typeAppui',
                fn ($q) => $q->where('id_service', $service->id_service)
            )
            ->with([
                'lignes.appui.typeAppui',
            ])
            ->get();

        foreach ($demandes as $demande) {

            $lignesDuService = $this->lignesDuService(
                $demande,
                $service
            );

            if ($lignesDuService->isEmpty()) {
                continue;
            }

            // Chercher un traitement déjà créé pour cette demande et ce service.
            $traitement = $demande->traitements()
                ->where('id_service', $service->id_service)
                ->firstOrFail();

            // Aucun traitement : le service prend le dossier en charge.
            if (! $traitement) {
                $traitement = Traitement::create([
                    'id_service' => $service->id_service,
                    'date_prise_en_charge' => now(),
                ]);
            }

            // Rattacher les lignes de ce service au traitement.
            $lignesDuService
                ->whereNull('id_traitement')
                ->each(function (LigneDemande $ligne) use ($traitement) {
                    $ligne->update([
                        'id_traitement' => $traitement->id_traitement,
                    ]);
                });
        }

        return redirect()
            ->route('instructions.index', [
                'service' => $service->id_service,
            ])
            ->with(
                'success',
                'Tous les dossiers affichés ont été pris en charge avec succès.'
            );
    }

    private function lignesDuService(Demande $demande, Service $service)
    {
        return $demande->lignes->filter(
            fn ($ligne) => $ligne->appui?->typeAppui?->id_service === $service->id_service
        );
    }

    private function traitementDuService(
        Demande $demande,
        Service $service
    ): ?Traitement {
        return $demande->traitements()
            ->where('id_service', $service->id_service)
            ->firstOrFail();
    }

    public function demanderCorrection(
        Request $request,
        string $idDemande
    ) {
        $service = $this->serviceDeLAgent();

        $demande = $this->demandeDuService(
            $idDemande,
            $service
        );

        if (! $demande->estSoumise()) {
            return redirect()
                ->route('instructions.show', $demande->id_demande)
                ->with(
                    'error',
                    'Seule une demande soumise peut être renvoyée pour correction.'
                );
        }

        $validated = $request->validate([
            'motif' => [
                'required',
                'string',
                'max:300',
            ],
        ]);

        $traitement = $demande->traitements()
            ->where('id_service', $service->id_service)
            ->firstOrFail();

        DemandeCorrection::create([
            'id_demande' => $demande->id_demande,
            'id_traitement' => $traitement->id_traitement,
            'motif' => $validated['motif'],
            'date_demande_correction' => now(),
        ]);

        $demande->update([
            'id_statut_demande' => StatutDemandeE::a_corriger,
        ]);

        return redirect()
            ->route('instructions.index')
            ->with(
                'success',
                'La demande a été renvoyée pour correction.'
            );
    }
}
