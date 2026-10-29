<?php

namespace App\Filament\Widgets;

use App\Services\DeviceHealthService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class DeviceHealthOverview extends Widget
{
    protected string $view = 'filament.widgets.device-health-overview';

    protected static ?string $pollingInterval = null;

    // ---- Diisi oleh subclass ----
    protected static string $namaPerangkat = '';
    protected static string $deviceKey = '';     // identitas perangkat HMI, mis. "sanding-hmi"
    protected static string $latestFirmwareKey = 'hmi'; // kunci di config/machines.php

    // ---- State yang dipakai Blade (nama sama dengan partial HMI) ----
    public string $machineNama = '-';
    public string $statusMesin = 'OFFLINE';
    public string $macAddress = '-';
    public string $firmwareVersion = '-';
    public bool $newFirmwareAvailable = false;
    public string $newFirmwareVersion = '-';
    public string $uptimeString = '-';
    public string $freeHeapKb = '-';
    public bool $lanOk = false;
    public bool $mqttOk = false;
    public bool $httpOk = false;

    public function mount(): void
    {
        if (static::$deviceKey === '') {
            throw new \RuntimeException(static::class . ' belum mengisi $deviceKey.');
        }

        $this->machineNama = static::$namaPerangkat ?: static::$deviceKey;

        $this->updateData();
    }
    public function updateData(): void
    {
        $status = app(DeviceHealthService::class)->bacaStatus(static::$deviceKey);

        $online = (bool) $status['online'];

        $this->statusMesin = $online ? 'ONLINE' : 'OFFLINE';
        $this->macAddress = $status['mac'] ?? '-';
        $this->firmwareVersion = $status['firmware'] ?? '-';
        $this->freeHeapKb = isset($status['free_heap'])
            ? round($status['free_heap'] / 1024) . ' KB'
            : '-';
        $this->uptimeString = $this->formatUptime($status['uptime'] ?? null);

        // Kalau perangkat belum mengirim status per-layanan, ikut status online.
        $this->lanOk = (bool) ($status['lan_ok'] ?? $online);
        $this->mqttOk = (bool) ($status['mqtt_ok'] ?? $online);
        $this->httpOk = (bool) ($status['http_ok'] ?? $online);

        $terbaru = config('machines.latest_firmware.' . static::$latestFirmwareKey);
        $this->newFirmwareVersion = $terbaru ?? '-';
        $this->newFirmwareAvailable = $terbaru !== null
            && $this->firmwareVersion !== '-'
            && $terbaru !== $this->firmwareVersion;
    }

    public function updateFirmware(): void
    {
        try {
            if ($this->statusMesin !== 'ONLINE') {
                throw new \RuntimeException('Perangkat HMI sedang OFFLINE, update tidak bisa dikirim.');
            }

            app(DeviceHealthService::class)->kirimPerintahOta(
                static::$deviceKey,
                $this->newFirmwareVersion
            );

            Notification::make()
                ->title('Perintah Update Terkirim')
                ->body("HMI akan memperbarui firmware ke {$this->newFirmwareVersion} dan restart.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()->title('Gagal Mengirim Update')->danger()->body($e->getMessage())->send();
        }
    }

    private function formatUptime(?int $detik): string
    {
        if (! $detik) {
            return '-';
        }

        return intdiv($detik, 3600) . ' Jam ' . intdiv($detik % 3600, 60) . ' Menit';
    }

    public static function canView(): bool
    {
        return static::$deviceKey !== '';
    }
}
