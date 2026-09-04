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
    public int $totalSanding2 = 0;
    public string $statusMesin = 'OFFLINE';
    public int $kecepatanSinyal = 0;
    public string $wifiSSID = '-';

    // Variabel Status Error Sensor dari ESP32
    public bool $sensor1Error = false;
    public bool $sensor2Error = false;

    protected static ?string $pollingInterval = '1s';

    public function mount(): void
    {
        $this->updateData();
    }

    public function updateData(): void
    {
        // Ambil nilai dari cache state MQTT
        $this->totalSanding = (int) cache()->get('veneer_jumlah_s1', 0);
        $this->totalSanding2 = (int) cache()->get('veneer_jumlah_s2', 0);

        $lastSeen = cache()->get('veneer_last_seen');

        if ($lastSeen && (time() - $lastSeen) <= 15) {
            $this->statusMesin = 'ONLINE';
            $this->kecepatanSinyal = (int) cache()->get('veneer_kecepatan', 0);
            $this->wifiSSID = cache()->get('veneer_ssid', 'Unknown WiFi');

            // Baca flag error langsung dari laporan ESP32
            $this->sensor1Error = (bool) cache()->get('veneer_s1_error', false);
            $this->sensor2Error = (bool) cache()->get('veneer_s2_error', false);
        } else {
            $this->statusMesin = 'OFFLINE';
            $this->kecepatanSinyal = 0;
            $this->wifiSSID = 'Disconnected';
            $this->sensor1Error = false;
            $this->sensor2Error = false;
        }
    }

    // Fungsi reset spesifik berdasarkan pilihan
    public function resetCounter(string $target = 'all'): void
    {
        try {
            $mqtt = MQTT::connection();

            $command = 'RESET_ALL';
            $pesanNotif = 'Semua counter berhasil di-reset.';

            if ($target === 'sensor1') {
                $command = 'RESET_S1';
                $pesanNotif = 'Counter Sensor 1 (Modal Sanding) berhasil di-reset.';
            } elseif ($target === 'sensor2') {
                $command = 'RESET_S2';
                $pesanNotif = 'Counter Sensor 2 (Hasil Sanding) berhasil di-reset.';
            }

            $mqtt->publish('pabrik/veneer/perintah', $command, 0);
            $mqtt->disconnect();

            Notification::make()
                ->title('Perintah Reset Terkirim')
                ->success()
                ->body($pesanNotif)
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
