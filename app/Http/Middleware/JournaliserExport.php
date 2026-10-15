<?php

namespace App\Http\Middleware;

use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Note au journal chaque export téléchargé (Excel, PDF) : qui a sorti quelles
 * données de l'application, quand et avec quels critères. Seuls les
 * téléchargements réussis sont notés.
 */
class JournaliserExport
{
    public function handle(Request $request, Closure $next): Response
    {
        $reponse = $next($request);

        if ($reponse->isSuccessful() && $request->user()) {
            JournalAction::consigner(ActionJournalEnum::exportTelecharge, null, array_filter([
                'document' => $request->route()?->getName(),
                'criteres' => $request->query() ?: null,
            ]));
        }

        return $reponse;
    }
}
