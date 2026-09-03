<?php

namespace App\Filament\Widgets;

use App\Models\VeneerLog;
use Filament\Widgets\Widget;

class SandingRealtimeOverview extends Widget
{
    protected string $view = 'filament.widgets.sanding-realtime-overview';

    public int $totalSanding = 0;
    public string $statusMesin = 'OFFLINE';
    public int $kecepatanSinyal = 0;

    protected static ?string $pollingInterval = '2s';

    public function mount(): void
    {
        $this->updateData();
    }

    public function updateData(): void
    {
        // 1. Ambil data hitungan terbaru dari MySQL
        $latestLog = VeneerLog::latest()->first();
        $this->totalSanding = $latestLog ? $latestLog->jumlah : 0;

        // 2. Ambil data kecepatan sinyal dari cache
        $this->kecepatanSinyal = (int) cache()->get('veneer_kecepatan', 0);

        // 3. Pengecekan Status ONLINE / OFFLINE
        $lastSeen = cache()->get('veneer_last_seen');

        // Jika ada data masuk dalam 15 detik terakhir -> ONLINE
        if ($lastSeen && (time() - $lastSeen) <= 15) {
            $this->statusMesin = 'ONLINE';
        } else {
            $this->statusMesin = 'OFFLINE';
        }
    }
}
