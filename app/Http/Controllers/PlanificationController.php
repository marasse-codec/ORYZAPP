<?php

namespace App\Http\Controllers;

use App\Domain\Shared\Services\LanceurArtisan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Déclencheur externe du planificateur, pour un hébergement sans cron (Render gratuit) :
 * un service de ping (cron-job.org…) appelle cette adresse chaque minute, comme le ferait
 * « * * * * * php artisan schedule:run ». Désactivé tant que CRON_TOKEN n'est pas défini.
 */
class PlanificationController extends Controller
{
    public function __invoke(Request $request, LanceurArtisan $lanceur): JsonResponse
    {
        $attendu = (string) config('services.cron.token');

        // Sans jeton configuré, la route n'existe pas.
        abort_if($attendu === '', 404);

        abort_unless(hash_equals($attendu, (string) $request->bearerToken()), 401);

        $lanceur->lancer('schedule:run');

        return response()->json(['statut' => 'lancé'], 202);
    }
}
