<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SandingKecil extends MachineRealtimeOverview
{
    protected string $view = 'filament.widgets.machine-realtime-overview';

    protected static string $machineSlug = 'sanding';
    protected static string $judulSensor1 = 'Hasil Sanding';
    protected static string $judulSensor2 = 'Modal Sanding';
    protected static string $satuanHitung = 'lembar';
}
