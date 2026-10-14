<?php

namespace App\Domain\Shared\Services;

use Symfony\Component\Process\Process;

/**
 * Lance une commande artisan en arrière-plan, sans attendre qu'elle finisse : une
 * sauvegarde dure plus longtemps qu'une requête web n'a le droit de durer.
 *
 * Isolé dans sa classe pour que les tests puissent le remplacer.
 */
class LanceurArtisan
{
    public function lancer(string $commande): void
    {
        // nohup + « & » : le shell rend la main aussitôt, la commande survit à la requête.
        Process::fromShellCommandline(
            sprintf('nohup %s %s %s > /dev/null 2>&1 &', escapeshellarg(PHP_BINARY), escapeshellarg(base_path('artisan')), $commande),
            base_path(),
        )->run();
    }
}
