<?php

namespace App\Domain\Stock\Observers;

use App\Domain\Notification\Services\RuleEngineService;
use App\Domain\Stock\Models\Appui;

class AppuiObserver
{
    public function updated(Appui $appui): void
    {
       RuleEngineService::evaluate($appui, 'updated');
    }
}