<?php

namespace App\Filament\Widgets;

class SandingHmiHealth extends DeviceHealthOverview
{
    protected static string $machineSlug = 'hmi_sanding';
    protected static string $deviceKey = 'sanding-hmi';
    protected static string $deviceType = 'input-display';
}
