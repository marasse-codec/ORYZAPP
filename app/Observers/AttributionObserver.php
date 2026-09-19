<?php

namespace App\Observers;

use App\Models\Attribution;
use App\Services\RuleEngineService;

class AttributionObserver
{
    /**
     * Handle the Attribution "created" event.
     */
    public function created(Attribution $attribution): void
    {
        //
    }

    /**
     * Handle the Attribution "updated" event.
     */
    public function updated(Attribution $attribution): void
    {
        // Déclenche l'évaluation du moteur de règles
        RuleEngineService::evaluate($attribution, 'updated');
    }

    /**
     * Handle the Attribution "deleted" event.
     */
    public function deleted(Attribution $attribution): void
    {
        //
    }

    /**
     * Handle the Attribution "restored" event.
     */
    public function restored(Attribution $attribution): void
    {
        //
    }

    /**
     * Handle the Attribution "force deleted" event.
     */
    public function forceDeleted(Attribution $attribution): void
    {
        //
    }
}
