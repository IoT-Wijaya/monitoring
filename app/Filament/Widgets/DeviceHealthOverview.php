<?php

namespace App\Filament\Widgets;

use App\Models\Machine;
use App\Services\DeviceHealthService;
use App\Services\FirmwareReleaseService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class DeviceHealthOverview extends Widget
{
    protected string $view = 'filament.widgets.device-health-overview';

    // Filament v4: properti non-static. Tanpa ini status tidak berubah di layar.
    protected ?string $pollingInterval = '5s';

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

    // ---- State tambahan dari telemetri HMI ----
    public string $ipAddress = '-';
    public ?int $httpCode = null;
    public int $httpStreak = 0;
    public string $httpOkAgo = '-';
    public string $sensorMode = '-';

    public function mount(): void
    {
        if (static::$deviceKey === '') {
            throw new \RuntimeException(static::class . ' belum mengisi $deviceKey.');
        }

        // Cari data di tabel machines berdasarkan deviceKey yang bertindak sebagai slug
        $machineRecord = Machine::where('slug', static::$deviceKey)->first();

        // Ambil nama dari database, jika tidak ketemu fallback ke deviceKey
        $this->machineNama = $machineRecord?->nama ?: static::$deviceKey;

        // Ambil MAC address jika ada
        if ($machineRecord && ! empty($machineRecord->mac_address)) {
            $this->macAddress = $machineRecord->mac_address;
        }

        $this->updateData();
    }

    public function updateData(): void
    {
        $s = app(DeviceHealthService::class)->bacaStatus(static::$deviceKey);

        $online = (bool) ($s['online'] ?? false);
        $this->statusMesin = $online ? 'ONLINE' : 'OFFLINE';

        if (! empty($s['mac']) && $s['mac'] !== '-') {
            $this->macAddress = $s['mac'];
        }

        $this->firmwareVersion = $s['firmware'] ?? '-';
        $this->freeHeapKb = isset($s['free_heap'])
            ? round($s['free_heap'] / 1024) . ' KB'
            : '-';
        $this->uptimeString = $this->formatUptime($s['uptime'] ?? null);

        $this->lanOk = (bool) ($s['lan_ok'] ?? false);
        $this->mqttOk = (bool) ($s['mqtt_ok'] ?? false);
        $this->httpOk = (bool) ($s['http_ok'] ?? false);

        $this->ipAddress = $s['ip'] ?? '-';
        $this->httpCode = $s['http_code'] ?? null;
        $this->httpStreak = (int) ($s['http_streak'] ?? 0);
        $this->httpOkAgo = (isset($s['http_ok_ago']) && $s['http_ok_ago'] >= 0)
            ? $s['http_ok_ago'] . ' detik lalu'
            : '-';
        $this->sensorMode = ($s['sensor'] ?? '') !== '' ? $s['sensor'] : '-';

        $terbaru = app(FirmwareReleaseService::class)->versiTerbaru(static::$latestFirmwareKey);

        $this->newFirmwareVersion = $terbaru ?? '-';

        // version_compare: awalan "v" diabaikan, panel muncul hanya bila rilis LEBIH BARU.
        $this->newFirmwareAvailable = $terbaru !== null
            && $this->firmwareVersion !== '-'
            && version_compare(
                ltrim($terbaru, 'vV'),
                ltrim($this->firmwareVersion, 'vV'),
                '>'
            );
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
                ->body("HMI akan memeriksa dan memasang firmware {$this->newFirmwareVersion} jika sedang idle.")
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
