<?php

namespace App\Observers;

use App\Models\Appui;
use App\Services\RuleEngineService;

class AppuiObserver
{
    public function updated(Appui $appui): void
    {
       RuleEngineService::evaluate($appui, 'updated');
    }
}