<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un compte désactivé pendant qu'il était connecté est déconnecté à sa
 * prochaine page, et renvoyé à la connexion avec une explication.
 */
class CompteActif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->estActif()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Ce compte a été désactivé. Adressez-vous à l\'administrateur.']);
        }

        return $next($request);
    }
}
