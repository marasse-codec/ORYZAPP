<?php

namespace App\Http\Controllers\Stock;

use App\Domain\Referentiel\Models\TypeAppui;
use App\Domain\Referentiel\Models\TypeSystemeProduction;
use App\Domain\Referentiel\Models\UniteDosage;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Services\CatalogueAppuiService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\EnregistrerAppuiRequest;
use App\Utils\Helper;
use Illuminate\Http\Request;

/** Le catalogue des appuis (magasin) : consultation, ajout, modification, suppression. */
class AppuiController extends Controller
{
    public function index(Request $request)
    {
        $recherche = trim((string) $request->query('q', ''));
        $idType = $request->query('type');

        $appuis = Appui::query()
            ->with(['typeAppui', 'uniteDosage'])
            ->when($recherche !== '', fn ($q) => $q->whereLike('libelle', "%{$recherche}%"))
            ->when($idType, fn ($q) => $q->where('id_type_appui', $idType))
            ->orderBy('libelle')
            ->paginate(15)
            ->withQueryString();

        return view('appuis.index', [
            'appuis' => $appuis,
            'recherche' => $recherche,
            'idType' => $idType,
            'types' => TypeAppui::orderBy('lib_type_appui')->get(),
            'layout' => Helper::layout(),
        ]);
    }

    public function create()
    {
        return view('appuis.form', $this->donneesFormulaire(new Appui(['seuil_alerte' => 0])));
    }

    public function store(EnregistrerAppuiRequest $request, CatalogueAppuiService $catalogue)
    {
        $appui = $catalogue->creer($request->validated());

        return redirect()->route('appuis.index')->with('success', "« {$appui->libelle} » a été ajouté au catalogue.");
    }

    public function edit(Appui $appui)
    {
        return view('appuis.form', $this->donneesFormulaire($appui));
    }

    public function update(EnregistrerAppuiRequest $request, Appui $appui, CatalogueAppuiService $catalogue)
    {
        $catalogue->modifier($appui, $request->validated());

        return redirect()->route('appuis.index')->with('success', "« {$appui->libelle} » a été mis à jour.");
    }

    public function destroy(Appui $appui, CatalogueAppuiService $catalogue)
    {
        $catalogue->supprimer($appui);

        return redirect()->route('appuis.index')->with('success', "« {$appui->libelle} » a été retiré du catalogue.");
    }

    private function donneesFormulaire(Appui $appui): array
    {
        return [
            'appui' => $appui,
            'types' => TypeAppui::orderBy('lib_type_appui')->get(),
            'unites' => UniteDosage::orderBy('lib_unite')->get(),
            'systemes' => TypeSystemeProduction::orderBy('lib_type_systeme_production')->get(),
            'systemesChoisis' => old('systemes', $appui->exists ? $appui->typesSystemesProductions()->pluck('types_systemes_productions.id_type_systeme_production')->all() : []),
            'layout' => Helper::layout(),
        ];
    }
}
