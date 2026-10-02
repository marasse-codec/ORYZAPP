<?php

namespace App\Http\Controllers;

use App\Models\Approvisionnement;
use App\Models\Appui;
use App\Services\ApprovisionnementService as ServicesApprovisionnementService;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Entrées de stock. Le magasin est le seul à incrémenter les quantités,
 * comme il est le seul à les décrémenter en servant les bons.
 */
class ApprovisionnementController extends Controller
{
    public function index()
    {

        $approvisionnements = Approvisionnement::with([
            'mouvements' => function($query) {
                $query->select(
                    'id_approvisionnement', 
                    'id_appui', 
                    DB::raw('SUM(quantite) as total_quantite')
                )
                ->groupBy('id_approvisionnement', 'id_appui')
                ->with('appui');
            }
        ])->get();

        return view('approvisionnements.index', [
            'approvisionnements' => $approvisionnements,
            'layout' => Helper::layout(),
        ]);
    }

    public function searchAppuis(Request $request)
    {
        $search = trim($request->input('q', ''));

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $appuis = Appui::with([
                'typeAppui',
            ])
            ->where('libelle', 'like', '%'.$search.'%')
            ->orderBy('libelle')
            ->limit(20)
            ->get()
            ->map(fn ($appui) => [
                'id' => $appui->id_appui,
                'libelle' => $appui->libelle,
                'type' => $appui->typeAppui?->libelle ?? 'Autres',
                'stock' => $appui->quantiteeEnStock(),
                'est_intrant' => $appui->typeAppui->est_intrant
            ]);

        return response()->json($appuis);

    }

    public function create()
    {
        return view('approvisionnements.create', [
            'layout' => Helper::layout(),
        ]);
    }

    public function store(Request $request)
    {
        $service = new ServicesApprovisionnementService;

        return $service->store($request);
    }
}
