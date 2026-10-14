<?php

namespace App\Http\Controllers\Commission;

use App\Domain\Commission\Services\SimulationSortieService;
use App\Http\Controllers\Controller;
use App\Utils\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * La simulation des sorties de stock. Chacun y pose ses hypothèses d'arbitrage
 * et voit les disponibilités fondre en direct, sans qu'aucune attribution ni
 * aucun décrément de stock ne soit écrit : c'est une aide à la décision, pas
 * une décision. Les vraies attributions se saisissent dossier par dossier dans
 * l'écran d'attribution.
 *
 * Le brouillon est personnel et ne dépend d'aucune séance : on simule à
 * n'importe quel moment.
 */
class SimulationSortieController extends Controller
{
    public function __construct(private SimulationSortieService $simulation) {}

    public function index(Request $request)
    {
        return view('attributions.simulation', [
            'postes' => $this->simulation->etat($request->user()),
            'layout' => Helper::layout(),
        ]);
    }

    /**
     * Note une quantité simulée. Appelé au fil de la saisie, d'où la réponse
     * JSON : la page ne se recharge pas, seule la persistance du brouillon a
     * besoin d'aller jusqu'au serveur.
     */
    public function noter(Request $request): JsonResponse
    {
        // Validation explicite : l'application ne rend du JSON sur exception que
        // sous « api/* » (bootstrap/app.php), or cette route ne parle que JSON.
        $validateur = Validator::make($request->all(), [
            'id_appui' => 'required|integer|exists:appuis,id_appui',
            // Rien n'interdit de dépasser le disponible : c'est justement la
            // tension que l'on vient mesurer.
            'quantite' => 'required|integer|min:0',
        ]);

        if ($validateur->fails()) {
            return response()->json(['message' => $validateur->errors()->first()], 422);
        }

        $valide = $validateur->validated();

        $this->simulation->noter($request->user(), (int) $valide['id_appui'], (int) $valide['quantite']);

        return response()->json(['enregistre' => true]);
    }

    public function reinitialiser(Request $request)
    {
        $this->simulation->reinitialiser($request->user());

        return redirect()->route('attributions.simulation')
            ->with('success', 'Votre simulation a été réinitialisée.');
    }
}
