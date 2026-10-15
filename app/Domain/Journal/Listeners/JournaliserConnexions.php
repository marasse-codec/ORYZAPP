<?php

namespace App\Domain\Journal\Listeners;

use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

/**
 * Garde la trace des connexions au journal : qui s'est connecté, quand, d'où
 * (adresse IP), et les tentatives refusées. Laravel prévient à chaque connexion,
 * échec ou déconnexion ; cette classe écoute ces trois événements.
 */
class JournaliserConnexions
{
    public function connexion(Login $evenement): void
    {
        $user = $evenement->user;

        // Un compte désactivé est refermé aussitôt par LoginRequest, qui note lui-même le refus.
        if (! $user instanceof User || ! $user->estActif()) {
            return;
        }

        $user->forceFill(['derniere_connexion_le' => now()])->saveQuietly();
        JournalAction::consigner(ActionJournalEnum::connexion, $user, [], $user);
    }

    public function echec(Failed $evenement): void
    {
        // L'adresse e-mail saisie est notée, jamais le mot de passe.
        $user = $evenement->user instanceof User ? $evenement->user : null;

        JournalAction::consigner(ActionJournalEnum::connexionEchouee, $user, [
            'email' => $evenement->credentials['email'] ?? null,
            'motif' => $user ? 'mot de passe incorrect' : 'adresse inconnue',
        ]);
    }

    public function deconnexion(Logout $evenement): void
    {
        $user = $evenement->user;

        if ($user instanceof User && $user->estActif()) {
            JournalAction::consigner(ActionJournalEnum::deconnexion, $user, [], $user);
        }
    }

    /** Branche chaque événement sur sa méthode (enregistré dans AppServiceProvider). */
    public function subscribe(Dispatcher $evenements): array
    {
        return [
            Login::class => 'connexion',
            Failed::class => 'echec',
            Logout::class => 'deconnexion',
        ];
    }
}
