<?php

namespace App\Console\Commands;

use App\Domain\Notification\Services\AlerteService;
use Illuminate\Console\Command;

class EnvoyerResumeQuotidien extends Command
{
    protected $signature = 'notifications:resume-quotidien';

    protected $description = 'Envoie le résumé quotidien aux agents de service et de commission (planifié chaque matin).';

    public function handle(AlerteService $alertes): int
    {
        $this->info($alertes->resumeQuotidien().' destinataire(s) notifié(s).');

        return self::SUCCESS;
    }
}
