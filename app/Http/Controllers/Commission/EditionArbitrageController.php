<?php

namespace App\Http\Controllers\Commission;

use App\Domain\Commission\Services\SyntheseAttributionService;
use App\Domain\Demande\Models\Demande;
use App\Http\Controllers\Controller;
use App\Utils\Helper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Les fiches d'arbitrage : un brouillon papier par demande, que la commission remplit
 * en séance et que l'agent saisit ensuite dans l'écran d'attribution. Elles se tirent
 * avant toute attribution, donc depuis les demandes qui attendent l'arbitrage.
 */
class EditionArbitrageController extends Controller
{
    /** Les demandes à arbitrer, dans le périmètre de l'utilisateur. */
    private function aArbitrer(): Builder
    {
        return Demande::query()->aArbitrer()->pourUtilisateur();
    }

    public function index()
    {
        return view('editions.arbitrages.index', [
            'demandes' => $this->aArbitrer()
                ->with(['acteur', 'typeActeur', 'localites', 'systemesProductions', 'lignes.typeAppui'])
                ->orderBy('date_soummission')
                ->get(),
            'layout' => Helper::layout(),
        ]);
    }

    /**
     * Les fiches des demandes cochées, ou de toutes celles à arbitrer si rien n'est coché.
     * Une demande déjà attribuée ou rejetée n'a plus de fiche à remplir : elle est ignorée.
     */
    public function imprimer(Request $request, SyntheseAttributionService $synthese)
    {
        $ids = $request->validate([
            'demandes' => 'nullable|array',
            'demandes.*' => 'integer',
        ])['demandes'] ?? [];

        $demandes = $this->aArbitrer()
            ->with([
                'acteur', 'typeActeur', 'campagne', 'localites',
                'systemesProductions.typeSystemeProduction',
                'lignes.typeAppui', 'lignes.appui.uniteDosage',
            ])
            ->when($ids, fn ($q) => $q->whereIn('id_demande', $ids))
            ->orderBy('date_soummission')
            ->get();

        // Pour chaque dossier, les appuis du catalogue qu'il vise, avec ce qui reste en stock.
        $catalogues = $demandes->mapWithKeys(fn (Demande $demande) => [
            $demande->id_demande => $synthese->confrontationPourDemande($demande),
        ]);

        return view('editions.arbitrages.fiche_arbitrage', [
            'demandes' => $demandes,
            'catalogues' => $catalogues,
        ]);
    }
}
