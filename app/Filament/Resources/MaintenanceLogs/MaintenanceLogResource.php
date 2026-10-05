<?php

namespace App\Filament\Resources\MaintenanceLogs;

use App\Filament\Resources\MaintenanceLogs\Pages\CreateMaintenanceLog;
use App\Filament\Resources\MaintenanceLogs\Pages\EditMaintenanceLog;
use App\Filament\Resources\MaintenanceLogs\Pages\ListMaintenanceLogs;
use App\Filament\Resources\MaintenanceLogs\Pages\ViewMaintenanceLog;
use App\Filament\Resources\MaintenanceLogs\Schemas\MaintenanceLogForm;
use App\Filament\Resources\MaintenanceLogs\Schemas\MaintenanceLogInfolist;
use App\Filament\Resources\MaintenanceLogs\Tables\MaintenanceLogsTable;
use App\Models\MaintenanceLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MaintenanceLogResource extends Resource
{
    protected static ?string $model = MaintenanceLog::class;

    protected static ?string $recordRouteKeyName = 'uuid';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static UnitEnum|string|null $navigationGroup = 'Maintenance';

    protected static ?string $recordTitleAttribute = 'MaintenanceLog';

    public static function form(Schema $schema): Schema
    {
        return MaintenanceLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MaintenanceLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceLogs::route('/'),
            'create' => CreateMaintenanceLog::route('/create'),
            'view' => ViewMaintenanceLog::route('/{record}'),
            'edit' => EditMaintenanceLog::route('/{record}/edit'),
        ];
    }
}
