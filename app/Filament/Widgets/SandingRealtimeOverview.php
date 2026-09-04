<?php

namespace App\Filament\Widgets;

use App\Models\VeneerLog;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;
use PhpMqtt\Client\Facades\MQTT;

class SandingRealtimeOverview extends Widget
{
    protected string $view = 'filament.widgets.sanding-realtime-overview';

    public int $totalSanding = 0;
    public string $statusMesin = 'OFFLINE';
    public int $kecepatanSinyal = 0;
    public string $wifiSSID = '-'; // Variabel untuk Nama SSID

    protected static ?string $pollingInterval = '2s';

    public function mount(): void
    {
        $this->updateData();
    }

    public function updateData(): void
    {
        $latestLog = VeneerLog::latest()->first();
        $this->totalSanding = $latestLog ? $latestLog->jumlah : 0;

        $lastSeen = cache()->get('veneer_last_seen');

        if ($lastSeen && (time() - $lastSeen) <= 15) {
            $this->statusMesin = 'ONLINE';
            $this->kecepatanSinyal = (int) cache()->get('veneer_kecepatan', 0);
            $this->wifiSSID = cache()->get('veneer_ssid', 'Unknown WiFi'); // Ambil SSID dari cache
        } else {
            $this->statusMesin = 'OFFLINE';
            $this->kecepatanSinyal = 0;
            $this->wifiSSID = 'Disconnected';
        }
    }

    public function resetCounter(): void
    {
        try {
            $mqtt = MQTT::connection();
            $mqtt->publish('pabrik/veneer/perintah', 'RESET', 0);
            $mqtt->disconnect();

            Notification::make()
                ->title('Perintah Reset Terkirim')
                ->success()
                ->body('ESP32 akan mereset hitungan ke 0.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Mengirim Perintah')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }
}
