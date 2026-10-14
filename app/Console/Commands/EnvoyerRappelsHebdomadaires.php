<?php

namespace App\Console\Commands;

use App\Domain\Notification\Services\AlerteService;
use Illuminate\Console\Command;

class EnvoyerRappelsHebdomadaires extends Command
{
    protected $signature = 'notifications:rappels
        {--bons=14 : Jours après lesquels un bon publié non retiré est rappelé}
        {--brouillons=7 : Jours après lesquels un brouillon de demande est rappelé}
        {--seances=30 : Jours après lesquels une séance ouverte est rappelée}';

    protected $description = 'Rappelle ce qui traîne : impayés, bons non retirés, brouillons oubliés, séances ouvertes (planifié chaque lundi).';

    public function handle(AlerteService $alertes): int
    {
        $envoyes = $alertes->rappelsHebdomadaires((int) $this->option('bons'), (int) $this->option('brouillons'), (int) $this->option('seances'));

        foreach ($envoyes as $sujet => $nombre) {
            $this->line("{$sujet} : {$nombre} destinataire(s)");
        }

        return self::SUCCESS;
    }
}
