<?php

use App\Domain\Administration\Services\SanteSystemeService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SNF05 : Sauvegarde quotidienne automatique de la base de données et des pièces téléversées à 2h du matin.
Schedule::command('backup:run')->dailyAt('02:00')->onOneServer()->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('02:30')->onOneServer();
Schedule::command('backup:monitor')->dailyAt('03:00')->onOneServer();

// Résumé quotidien : à 7 h, pour les agents qui n'ont pas à être alertés en continu.
Schedule::command('notifications:resume-quotidien')->dailyAt('07:00')->onOneServer()->withoutOverlapping();

// Rappels du lundi matin : impayés, bons non retirés, brouillons oubliés, séances à clôturer.
Schedule::command('notifications:rappels')->weeklyOn(1, '08:00')->onOneServer()->withoutOverlapping();

// Le planificateur note qu'il est passé : l'écran « Santé du système » s'en sert pour savoir s'il tourne.
Schedule::call(fn () => Cache::forever(SanteSystemeService::CLE_PLANIFICATEUR, now()->toIso8601String()))->everyMinute()->name('sante-planificateur');
