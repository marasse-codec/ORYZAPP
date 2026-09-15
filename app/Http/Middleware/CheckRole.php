<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // 1. Vérifier si l'utilisateur est connecté
        if (! $request->user()) {
            return redirect('login');
        }

        // 2. Vérifier si le rôle correspond (on utilise la relation 'role' dans User)
        // On suppose que ton modèle Role a un champ 'nom' (ex: 'admin')
        if ($request->user()->roles && $request->user()->hasRole($role)) {
            return $next($request);
        }

        // 3. Sinon, accès refusé
        abort(403, 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.');
    }
}
