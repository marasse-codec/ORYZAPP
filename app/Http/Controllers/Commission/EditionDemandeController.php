<?php

namespace App\Http\Controllers\Commission;

use App\Domain\Suivi\Exports\DemandesExport;
use App\Http\Controllers\Controller;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/** Les éditions de demandes : l'écran de configuration et l'export Excel. */
class EditionDemandeController extends Controller
{
    public function index()
    {
        return view('editions.demandes.index', [
            'demandes' => DemandesExport::perimetre()->with(['acteur', 'systemesProductions'])->orderByDesc('id_demande')->limit(50)->get(),
            'layout' => Helper::layout(),
        ]);
    }

    public function exportDemandes(Request $request)
    {
        $valide = $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'colonnes' => 'nullable|array',
            'colonnes.*' => 'string',
        ]);

        return Excel::download(
            new DemandesExport($valide['date_debut'] ?? null, $valide['date_fin'] ?? null, $valide['colonnes'] ?? []),
            'Oryza_Demandes_'.now()->format('Y_m_d_His').'.xlsx',
        );
    }
}
