<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SNF05 : Sauvegarde quotidienne automatique de la base de données et des pièces téléversées à 2h du matin.
Schedule::command('backup:run')->dailyAt('02:00');