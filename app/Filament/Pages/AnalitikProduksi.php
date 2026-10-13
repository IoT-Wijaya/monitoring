<?php

namespace App\Filament\Pages;

use App\Livewire\ProductionShiftChart;
use App\Models\Machine;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use UnitEnum;

class AnalitikProduksi extends Page
{
    use HasPageShield;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Analitik Produksi';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Grafik Produksi per Shift';

    protected string $view = 'filament.pages.analitik-produksi';

    protected function getHeaderWidgets(): array
    {
        return Machine::orderBy('nama')
            ->get()
            ->map(fn(Machine $machine) => ProductionShiftChart::make([
                'machineId' => $machine->id,
            ]))
            ->all();
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return ['default' => 1, 'lg' => 2];
    }
}
