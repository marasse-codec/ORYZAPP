<?php

namespace App\Http\Controllers;

use App\Models\SessionCommission;
use App\Services\SimulationSortieService;
use App\Utils\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * La simulation des sorties de stock. La commission y pose ses hypothèses
 * d'arbitrage et voit les disponibilités fondre en direct, sans qu'aucune
 * attribution ni aucun décrément de stock ne soit écrit : c'est une aide à la
 * décision, pas une décision. Les vraies attributions se saisissent dossier par
 * dossier dans l'écran d'attribution.
 *
 * Le brouillon est commun à toute la séance : chaque membre écrit dans le même
 * tableau et le relit au fil de l'eau.
 */
class SimulationSortieController extends Controller
{
    public function __construct(private SimulationSortieService $simulation) {}

    public function index()
    {
        $seance = $this->seanceOuverte();

        return view('attributions.simulation', [
            'postes' => $this->simulation->etat($seance),
            'seance' => $seance,
            'layout' => Helper::layout(),
        ]);
    }

    /**
     * L'état courant du brouillon commun, relu périodiquement par l'écran :
     * sans cela, un membre continuerait d'arbitrer sur les chiffres figés au
     * chargement de sa page pendant que ses collègues saisissent.
     */
    public function etat(): JsonResponse
    {
        $seance = $this->seanceOuverte();

        return response()->json([
            'postes' => $this->simulation->etat($seance),
            'seance_ouverte' => $seance !== null,
        ]);
    }

    /**
     * Note une quantité simulée. Appelé au fil de la saisie, d'où la réponse
     * JSON : la page ne se recharge pas, seule la persistance du brouillon a
     * besoin d'aller jusqu'au serveur.
     */
    public function noter(Request $request): JsonResponse
    {
        $seance = $this->seanceOuverte();

        if ($seance === null) {
            return response()->json(['message' => "Aucune séance de commission n'est ouverte."], 409);
        }

        // Validation explicite : l'application ne rend du JSON sur exception que
        // sous « api/* » (bootstrap/app.php), or cette route ne parle que JSON.
        $validateur = Validator::make($request->all(), [
            'id_appui' => 'required|integer|exists:appuis,id_appui',
            // Rien n'interdit de dépasser le disponible : c'est justement la
            // tension que la commission vient mesurer.
            'quantite' => 'required|integer|min:0',
        ]);

        if ($validateur->fails()) {
            return response()->json(['message' => $validateur->errors()->first()], 422);
        }

        $valide = $validateur->validated();

        $this->simulation->noter($seance, (int) $valide['id_appui'], (int) $valide['quantite']);

        return response()->json(['enregistre' => true]);
    }

    public function reinitialiser()
    {
        $seance = $this->seanceOuverte();

        if ($seance === null) {
            return redirect()->route('attributions.simulation')
                ->with('error', "Aucune séance ouverte : il n'y a pas de brouillon à vider.");
        }

        $this->simulation->reinitialiser($seance);

        return redirect()->route('attributions.simulation')
            ->with('success', 'Simulation réinitialisée pour toute la séance.');
    }

    private function seanceOuverte(): ?SessionCommission
    {
        return SessionCommission::whereNull('date_fin_session')->first();
    }
}
