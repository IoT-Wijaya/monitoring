<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use UnitEnum;

class RekapitulasiHarian extends Page
{
    use HasPageShield;
    protected string $view = 'filament.pages.rekapitulasi-harian';
    protected static ?string $navigationLabel = 'Rekapitulasi Harian';
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-table-cells';
    protected static UnitEnum|string|null $navigationGroup = 'Riwayat';
    protected static ?int $navigationSort = 1;
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
