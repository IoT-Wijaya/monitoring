<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class RekapitulasiHarian extends Page
{
    protected string $view = 'filament.pages.rekapitulasi-harian';
    protected static ?string $navigationLabel = 'Rekapitulasi Harian';
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-table-cells';
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
