<?php

namespace App\Http\Controllers\Demande;

use App\Domain\Demande\Models\BrouillonSaisie;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Brouillon de la demande en cours de saisie, propre à l'utilisateur connecté. */
class BrouillonDemandeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $brouillon = $this->requete($request)->first();

        // json(null) serait émis en « {} », objet vide que le client prendrait pour un brouillon.
        if (! $brouillon) {
            return response()->json(null, 204);
        }

        return response()->json([
            'donnees' => $brouillon->donnees,
            'enregistre_le' => $brouillon->updated_at->toIso8601String(),
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $donnees = $request->validate(['donnees' => ['required', 'array']])['donnees'];

        // Garde-fou : un formulaire de demande tient en quelques Ko.
        abort_if(strlen(json_encode($donnees)) > 100_000, 413, 'Brouillon trop volumineux.');

        $brouillon = BrouillonSaisie::updateOrCreate(
            ['id_user' => $request->user()->id, 'type' => BrouillonSaisie::TYPE_DEMANDE],
            ['donnees' => $donnees],
        );

        return response()->json(['enregistre_le' => $brouillon->updated_at->toIso8601String()]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->requete($request)->delete();

        return response()->json(null, 204);
    }

    private function requete(Request $request)
    {
        return BrouillonSaisie::where('id_user', $request->user()->id)->where('type', BrouillonSaisie::TYPE_DEMANDE);
    }
}
