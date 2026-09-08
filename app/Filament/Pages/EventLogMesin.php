<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use UnitEnum;

class EventLogMesin extends Page
{
    use HasPageShield;
    protected string $view = 'filament.pages.event-log-mesin';

    protected static ?string $navigationLabel = 'Log Mesin';
    protected static ?string $title = 'Log Mesin';
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static UnitEnum|string|null $navigationGroup = 'Riwayat';
    protected static ?int $navigationSort = 3;

    public function getCachedSubNavigation(): array
    {
        return [];
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
