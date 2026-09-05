<?php

namespace App\Filament\Widgets;

use Filament\Support\Enums\Width;

class SandingRealtimeOverview extends MachineRealtimeOverview
{
    protected static string $machineSlug = 'veneer';
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
