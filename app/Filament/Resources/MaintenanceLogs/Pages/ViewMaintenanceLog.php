<?php

namespace App\Filament\Resources\MaintenanceLogs\Pages;

use App\Filament\Resources\MaintenanceLogs\MaintenanceLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMaintenanceLog extends ViewRecord
{
    protected static string $resource = MaintenanceLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
