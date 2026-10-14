<?php

use App\Domain\Shared\Exceptions\RegleMetierViolee;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Derrière le proxy HTTPS de l'hébergeur (Render) : sans cela, les URL générées sont en http.
        $middleware->trustProxies(at: '*');

        // Pendant l'installation au démarrage (docker/entrypoint.sh) le site est en maintenance :
        // le contrôle de santé de l'hébergeur doit pourtant continuer à répondre.
        $middleware->preventRequestsDuringMaintenance(except: ['up']);

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Une règle du métier refuse l'opération : l'utilisateur en reçoit la raison, sur la page d'où il vient.
        $exceptions->render(function (RegleMetierViolee $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 409);
            }

            return back()->withInput()->with('error', $e->getMessage());
        });
    })->create();
