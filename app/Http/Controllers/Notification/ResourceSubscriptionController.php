<?php

namespace App\Http\Controllers\Notification;

use App\Domain\Stock\Models\Appui;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResourceSubscriptionController extends Controller
{
    public function toggle(Request $request, string $type, int $id): JsonResponse
    {
        // Résolution dynamique du type de ressource
        $modelClass = match($type) {
            'appui' => Appui::class,
            default => abort(404, "Type de ressource invalide"),
        };

        $resource = $modelClass::findOrFail($id);

        // Vérification du droit d'accès via les Policies de la ressource
        //$this->authorize('view', $resource);

        $isSubscribed = $resource->toggleSubscription($request->user());

        return response()->json([
            'subscribed' => $isSubscribed,
            'message' => $isSubscribed ? 'Alerte activée avec succès.' : 'Alerte désactivée.'
        ]);
    }
}