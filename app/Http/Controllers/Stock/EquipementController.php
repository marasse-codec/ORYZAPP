<?php

namespace App\Http\Controllers\Stock;

use App\Domain\Stock\Models\Equipement;
use App\Http\Controllers\Controller;
use App\Utils\Helper;
use Illuminate\Http\Request;

/** Le parc de matériel suivi à l'unité (motopompes, décortiqueuses…) : où est chaque équipement, et dans quel état. */
class EquipementController extends Controller
{
    public function index(Request $request)
    {
        $recherche = trim((string) $request->query('q', ''));
        $etat = $request->query('etat');
        $situation = $request->query('situation'); // « remis » ou « parking »

        $equipements = Equipement::query()
            ->with('appui')
            ->when($recherche !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereLike('matricule_equipement', "%{$recherche}%")
                ->orWhereHas('appui', fn ($a) => $a->whereLike('libelle', "%{$recherche}%"))))
            ->when($etat, fn ($q) => $q->where('etat', $etat))
            ->when($situation === 'remis', fn ($q) => $q->where('en_service', true))
            ->when($situation === 'parking', fn ($q) => $q->where('en_service', false))
            ->orderBy('matricule_equipement')
            ->paginate(20)
            ->withQueryString();

        return view('equipements.index', [
            'equipements' => $equipements,
            'recherche' => $recherche,
            'etat' => $etat,
            'situation' => $situation,
            'etats' => Equipement::query()->whereNotNull('etat')->distinct()->orderBy('etat')->pluck('etat'),
            'effectif' => [
                'total' => Equipement::count(),
                'remis' => Equipement::where('en_service', true)->count(),
            ],
            'layout' => Helper::layout(),
        ]);
    }
}
