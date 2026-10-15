<?php

namespace App\Console\Commands;

use App\Domain\Administration\Enums\ParametreEnum;
use App\Domain\Administration\Services\ParametreService;
use App\Domain\Notification\Services\AlerteService;
use Illuminate\Console\Command;

class EnvoyerRappelsHebdomadaires extends Command
{
    // Sans option, les délais viennent des paramètres généraux réglés par l'administrateur.
    protected $signature = 'notifications:rappels
        {--bons= : Jours après lesquels un bon publié non retiré est rappelé}
        {--brouillons= : Jours après lesquels un brouillon de demande est rappelé}
        {--seances= : Jours après lesquels une séance ouverte est rappelée}';

    protected $description = 'Rappelle ce qui traîne : impayés, bons non retirés, brouillons oubliés, séances ouvertes (planifié chaque lundi).';

    public function handle(AlerteService $alertes, ParametreService $parametres): int
    {
        $envoyes = $alertes->rappelsHebdomadaires(
            (int) ($this->option('bons') ?? $parametres->entier(ParametreEnum::rappelBonsJours)),
            (int) ($this->option('brouillons') ?? $parametres->entier(ParametreEnum::rappelBrouillonsJours)),
            (int) ($this->option('seances') ?? $parametres->entier(ParametreEnum::rappelSeancesJours)),
        );

        foreach ($envoyes as $sujet => $nombre) {
            $this->line("{$sujet} : {$nombre} destinataire(s)");
        }

        return self::SUCCESS;
    }
}
