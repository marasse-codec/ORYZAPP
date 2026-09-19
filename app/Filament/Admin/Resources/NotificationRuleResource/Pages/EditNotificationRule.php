<?php

namespace App\Filament\Admin\Resources\NotificationRuleResource\Pages;

use App\Filament\Admin\Resources\NotificationRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotificationRule extends EditRecord
{
    protected static string $resource = NotificationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
