<?php

namespace App\Http\Controllers\Demande;

use App\Domain\Acces\Enums\PermissionEnum;
use App\Domain\Commission\Services\SyntheseCommissionService;
use App\Domain\Demande\Enums\StatutDemandeE;
use App\Domain\Demande\Models\BrouillonSaisie;
use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Services\CreationDemandeService;
use App\Domain\Demande\Services\FormulaireDemandeService;
use App\Domain\Demande\Services\SoumissionDemandeService;
use App\Domain\Demande\Services\UpdateDemandeService;
use App\Domain\Demande\Services\VisiteDemandeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Demande\DemandeMarquerVisiteRequest;
use App\Http\Requests\Demande\StoreDemandeRequest;
use App\Http\Requests\Demande\UpdateDemandeRequest;
use App\Utils\Helper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemandeController extends Controller
{
    /** La liste des demandes visibles par l'utilisateur, recherchable (n°, entité, représentant, téléphone), filtrable par statut et paginée. */
    public function index(Request $request, SyntheseCommissionService $synthese)
    {
        $recherche = trim((string) $request->query('q', ''));
        $statut = StatutDemandeE::tryFrom((int) $request->query('statut'));

        $demandes = Demande::query()
            ->pourUtilisateur(Auth::user())
            ->attribuables()
            ->with(['acteur', 'localites.departement', 'attribution'])
            ->when($recherche !== '', fn ($q) => $q->where(fn ($q) => $q
                ->when(ctype_digit(ltrim($recherche, '#')), fn ($q) => $q->orWhere('id_demande', (int) ltrim($recherche, '#')))
                ->orWhereLike('nom_structure', "%{$recherche}%")
                ->orWhereHas('acteur', fn ($a) => $a->whereLike('nom', "%{$recherche}%")->orWhereLike('telephone', "%{$recherche}%"))))
            ->when($statut, fn ($q) => $q->whereRaw('('.Demande::statutSql().') = ?', [$statut->value]))
            ->latest('id_demande')
            ->paginate(20)
            ->withQueryString();

        // La confrontation avec les stocks ne s'affiche que pour qui arbitre :
        // ailleurs, l'état des stocks n'a pas à transparaître.
        $peutArbitrer = Auth::user()->can(PermissionEnum::gererCommission->value);
        $dossiers = $peutArbitrer ? $synthese->dossiersEnAttente() : collect();

        return view('demandes.index', [
            'demandes' => $demandes,
            'recherche' => $recherche,
            'statut' => $statut,
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

    public function create(FormulaireDemandeService $formulaire)
    {
        return view('demandes.create', $formulaire->donnees() + ['layout' => Helper::layout()]);
    }

    public function store(StoreDemandeRequest $request, CreationDemandeService $service)
    {
        $demande = $service->creer($request->validated(), $request->file('pieces', []));

        // La demande est enregistrée : le brouillon de saisie n'a plus lieu d'être.
        BrouillonSaisie::where('id_user', $request->user()->id)->where('type', BrouillonSaisie::TYPE_DEMANDE)->delete();

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

        // Pas de soumission sans visite de terrain : l'écran n'offre pas le bouton, le serveur refuse aussi.
        if (! $demande->terrainAEteVisite()) {
            return redirect()
                ->route('demandes.index')
                ->with('error', 'Enregistrez d\'abord la visite de terrain de la demande n°'.$demande->id_demande.' avant de la soumettre.');
        }

        app(SoumissionDemandeService::class)->soumettre($demande);

        return redirect()
            ->route('demandes.show', $demande->id_demande)
            ->with('success', 'La demande a été soumise avec succès.');
    }

    public function edit(string $id, FormulaireDemandeService $formulaire)
    {
        $demande = Demande::with([
            'acteur',
            'typeActeur',
            'localites.departement',
            'systemesProductions.typeSystemeProduction',
            'lignes.typeAppui.service',
            'lignes.appui.uniteDosage',
            'piecesDemandes.typePiece',
            'campagne',
        ])->findOrFail($id);

        $reference = $formulaire->donnees();

        // Les lignes d'appuis existantes (ou la saisie qu'on vient de refuser : old()).
        $lignesDB = $demande->lignes->map(fn ($ligne) => [
            'id_type_appui' => (string) ($ligne->id_type_appui ?? $ligne->appui?->id_type_appui ?? ''),
            'id_appui' => (string) ($ligne->id_appui ?? ''),
            'quantite' => $ligne->quantite_demandee ?? '',
        ])->toArray();

        $lignesInitiales = old('lignes', empty($lignesDB) ? [['id_type_appui' => '', 'id_appui' => '', 'quantite' => '']] : $lignesDB);

        // Les superficies existantes, ventilées par système de production.
        $superficiesDB = [];
        foreach ($reference['typesSystemesProductions'] as $systeme) {
            $existant = $demande->systemesProductions->firstWhere('id_type_systeme_production', $systeme->id_type_systeme_production);
            $superficiesDB[$systeme->id_type_systeme_production] = $existant ? (float) $existant->superficie : '';
        }

        return view('demandes.edit', $reference + [
            'demande' => $demande,
            'layout' => Helper::layout(),
            'lignesInitiales' => array_values($lignesInitiales),
            'superficiesInitiales' => old('superficies', $superficiesDB),
            // Premier département pour l'onglet des localités.
            'premierDepartementId' => $demande->localites->first()?->id_departement ?? '',
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

        app(VisiteDemandeService::class)->enregistrer($demande, $validatedData['observations']);

        return redirect()
            ->route('demandes.index')
            ->with('success', 'La demande n°' . $demande->id_demande . ' a été mise à jour avec succès.');

    }
}
