<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SandingHmiHealth;
use App\Filament\Widgets\SandingKecil;
use App\Filament\Widgets\SandingRealtimeOverview;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected string $view = 'filament.pages.dashboard';

    public function getCountingWidgets(): array
    {
        return $this->hanyaYangBolehTampil([
            SandingKecil::class,
            SandingRealtimeOverview::class,
        ]);
    }

    /**
     * Section 2: widget HMI health.
     */
    public function getHmiWidgets(): array
    {
        return $this->hanyaYangBolehTampil([
            SandingHmiHealth::class,
            // CncHmiHealth::class,
        ]);
    }

    /**
     * Membuang widget yang canView()-nya false (mis. karena izin akses).
     * Ditulis sendiri supaya tidak bergantung pada method internal Filament.
     */
    private function hanyaYangBolehTampil(array $widgets): array
    {
        return array_values(array_filter(
            $widgets,
            fn(string $widget): bool => $widget::canView(),
        ));
    }
}
