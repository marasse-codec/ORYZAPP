<?php

namespace App\Http\Controllers\Demande;

use App\Domain\Demande\Services\CreationDemandeSerieService;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Referentiel\Models\Localite;
use App\Domain\Referentiel\Models\TypeActeur;
use App\Domain\Referentiel\Models\TypeAppui;
use App\Http\Controllers\Controller;
use App\Http\Requests\Demande\StoreDemandeSerieRequest;
use App\Utils\Helper;

/**
 * Saisie en série : enregistrer d'un coup les demandes de plusieurs acteurs qui
 * demandent la même chose (même campagne, mêmes localités, mêmes appuis).
 */
class DemandeSerieController extends Controller
{
    public function create()
    {
        $typesAppuis = TypeAppui::with('appuis.uniteDosage')->orderBy('lib_type_appui')->get();

        return view('demandes.serie', [
            'layout' => Helper::layout(),
            'campagnes' => Campagne::ouvertes()->orderByDesc('mois_debut_campagne')->get(),
            'campagneParDefaut' => Campagne::enCours()?->id_campagne,
            'typesActeurs' => TypeActeur::orderBy('lib_type_acteur')->get(),
            'localites' => Localite::with('departement')->orderBy('lib_localite')->get(),
            // Pour Alpine : chaque type d'appui et les appuis qu'il regroupe.
            'catalogue' => $typesAppuis->map(fn (TypeAppui $type) => [
                'id' => $type->id_type_appui,
                'libelle' => $type->lib_type_appui,
                'appuis' => $type->appuis->map(fn ($appui) => [
                    'id' => $appui->id_appui,
                    'libelle' => $appui->libelle,
                    'unite' => $appui->uniteDosage?->lib_unite ?? '',
                ])->values(),
            ])->values(),
            'maxActeurs' => StoreDemandeSerieRequest::MAX_ACTEURS,
        ]);
    }

    public function store(StoreDemandeSerieRequest $request, CreationDemandeSerieService $serie)
    {
        $valide = $request->validated();
        $soumettre = $request->boolean('soumettre');

        $demandes = $serie->creer($valide, $valide['acteurs'], $soumettre);

        return redirect()
            ->route('demandes.index')
            ->with('success', $demandes->count().' demande(s) enregistrée(s)'.($soumettre ? ' et soumise(s).' : ' en brouillon : pensez à les soumettre.'));
    }
}
