<?php

namespace App\Http\Controllers\Commission;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Suivi\Exports\AttributionsExport;
use App\Http\Controllers\Controller;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EditionAttributionController extends Controller
{
    public function index()
    {

        $attributions = Attribution::query()
            ->with(['lignesAttributions.appui', 'demande.acteur', 'session'])
            ->orderByDesc('id_attribution')
            ->user()
            ->get();

        return view('editions.attributions.index', [
            'attributions' => $attributions,
            'layout' => Helper::layout(),
        ]);
    }

    public function exportAttributions(Request $request)
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $colonnes = $request->input('colonnes', []);

        $nomFichier = 'Oryza_Attributions_' . now()->format('Y_m_d_His') . '.xlsx';

        // Déclenche la génération et le téléchargement direct
        return Excel::download(new AttributionsExport($dateDebut, $dateFin, $colonnes), $nomFichier);
    }
}