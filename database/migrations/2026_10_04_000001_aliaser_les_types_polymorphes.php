<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le code est rangé par contexte : les classes ont changé de namespace. Les types
 * polymorphes déjà enregistrés (journal, abonnements) portaient l'ancien nom de
 * classe ; ils passent à l'alias que le code utilise désormais (cf. AppServiceProvider).
 * Les notifications déjà stockées gardent leur trace sous leur nouveau nom de classe.
 */
return new class extends Migration
{
    private const ALIAS = [
        'App\\Models\\Demande' => 'demande',
        'App\\Models\\Attribution' => 'attribution',
        'App\\Models\\SessionCommission' => 'session_commission',
        'App\\Models\\Approvisionnement' => 'approvisionnement',
        'App\\Models\\Equipement' => 'equipement',
        'App\\Models\\Appui' => 'appui',
    ];

    private const NOTIFICATIONS = [
        'App\\Notifications\\SuiviDossierNotification' => 'App\\Domain\\Notification\\Notifications\\SuiviDossierNotification',
        'App\\Notifications\\RessourceAlerteNotification' => 'App\\Domain\\Notification\\Notifications\\RessourceAlerteNotification',
    ];

    public function up(): void
    {
        foreach (self::ALIAS as $classe => $alias) {
            DB::table('journals_actions')->where('cible_type', $classe)->update(['cible_type' => $alias]);
            DB::table('resource_subscriptions')->where('subscribable_type', $classe)->update(['subscribable_type' => $alias]);
        }

        foreach (self::NOTIFICATIONS as $ancien => $nouveau) {
            DB::table('notifications')->where('type', $ancien)->update(['type' => $nouveau]);
        }

        // Les travaux en attente sérialisent d'anciens noms de classes : on les purge (ils seraient illisibles).
        DB::table('jobs')->delete();
    }

    public function down(): void
    {
        foreach (self::ALIAS as $classe => $alias) {
            DB::table('journals_actions')->where('cible_type', $alias)->update(['cible_type' => $classe]);
            DB::table('resource_subscriptions')->where('subscribable_type', $alias)->update(['subscribable_type' => $classe]);
        }
    }
};
