<?php

namespace App\Http\Controllers\Stock;

use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\MouvementStock;
use App\Domain\Stock\Models\TypeMouvementStock;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MouvementStockController extends Controller
{
    /**
     * Liste filtrée et paginée des mouvements de stock.
     */
    public function index(Request $request)
    {
        $query = MouvementStock::with([
            'typeMouvement',
            'appui',
            'equipement',
            'agent',
            'approvisionnement',
            'attribution',
        ]);

        // Filtrer par type de mouvement (Entrée, Sortie, Ajustement, etc.)
        if ($request->filled('id_type_mouvement')) {
            $query->where('id_type_mouvement_stock', $request->id_type_mouvement);
        }

        // Filtrer par appui
        if ($request->filled('id_appui')) {
            $query->where('id_appui', $request->id_appui);
        }

        // Filtrer par plage de dates
        if ($request->filled('date_debut')) {
            $query->whereDate('date_mouvement', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date_mouvement', '<=', $request->date_fin);
        }

        // Recherche textuelle sur l'appui ou l'agent
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('appui', fn ($a) => $a->whereLike('libelle', "%{$search}%"))
                  ->orWhereHas('agent', fn ($ag) => $ag->whereLike('nom', "%{$search}%"));
            });
        }

        $mouvements = $query->latest('date_mouvement')
            ->latest('id_mouvement_stock')
            ->paginate(15)
            ->withQueryString();

        return view('mouvementsStocks.index', [
            'mouvements' => $mouvements,
            'typesMouvements' => TypeMouvementStock::orderBy('libelle_type_mouvement')->get(),
            'appuis' => Appui::orderBy('libelle')->get(),
        ]);
    }

    /**
     * DÉtail d'un mouvement de stock spécifique.
     */
    public function show($id)
    {
        $mouvement = MouvementStock::with([
            'typeMouvement',
            'appui',
            'equipement',
            'agent',
            'approvisionnement',
            'attribution.demande.acteur',
        ])->findOrFail($id);

        return view('mouvementsStocks.show', compact('mouvement'));
    }
}