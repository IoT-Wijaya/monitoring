<?php

namespace App\Filament\Widgets;

use Filament\Support\Enums\Width;

class SandingRealtimeOverview extends MachineRealtimeOverview
{
    protected static string $machineSlug = 'veneer';
    protected static string $judulSensor1 = 'Hasil Sanding';
    protected static string $judulSensor2 = 'Modal Sanding';

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
