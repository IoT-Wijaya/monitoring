<?php

namespace App\Filament\Widgets;

use App\Models\Machine;
use App\Services\DeviceHealthService;
use App\Services\FirmwareReleaseService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class DeviceHealthOverview extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.widgets.device-health-overview';

    protected ?string $pollingInterval = '5s';

    // Lewat dari ini tanpa kabar = dianggap gagal.
    private const OTA_BATAS_DETIK = 300;

    // ---- Diisi oleh subclass ----
    protected static string $namaPerangkat = '';
    protected static string $deviceKey = '';
    protected static string $deviceType = 'input-display';

    // ---- State yang dipakai Blade ----
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

    public string $ipAddress = '-';
    public ?int $httpCode = null;
    public int $httpStreak = 0;
    public string $httpOkAgo = '-';
    public string $sensorMode = '-';

    // ---- State proses update (OTA) ----
    public bool $otaBerjalan = false;
    public int $otaDetik = 0;
    public string $otaTarget = '-';
    public ?int $otaPersen = null;
    public string $otaPesan = '';

    public function mount(): void
    {
        if (static::$deviceKey === '') {
            throw new \RuntimeException(static::class . ' belum mengisi $deviceKey.');
        }

        $machineRecord = Machine::where('slug', static::$deviceKey)->first();

        $this->machineNama = $machineRecord?->nama ?: static::$deviceKey;

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

        $terbaru = app(FirmwareReleaseService::class)->versiTerbaru(static::$deviceType);
        $this->newFirmwareVersion = $terbaru ?? '-';
        $this->newFirmwareAvailable = $terbaru !== null
            && $this->firmwareVersion !== '-'
            && version_compare(
                ltrim($terbaru, 'vV'),
                ltrim($this->firmwareVersion, 'vV'),
                '>'
            );

        $this->periksaStatusOta($s['ota'] ?? null);
    }

    // Saat update berjalan, polling dipercepat supaya progres terasa langsung.
    protected function getPollingInterval(): ?string
    {
        return $this->otaBerjalan ? '2s' : $this->pollingInterval;
    }

    /**
     * Tombol sebagai Action Filament: ->requiresConfirmation() menampilkan modal
     * bertema, menggantikan wire:confirm (dialog bawaan browser).
     */
    public function updateFirmwareAction(): Action
    {
        return Action::make('updateFirmware')
            ->label('Update Sekarang')
            ->icon('heroicon-m-arrow-path')
            ->color('warning')
            ->size('xs')
            ->requiresConfirmation()
            ->modalHeading('Update firmware HMI?')
            ->modalDescription(fn() => "HMI akan diperbarui ke {$this->newFirmwareVersion}. Perangkat akan restart dan sempat offline beberapa saat.")
            ->modalSubmitActionLabel('Ya, update sekarang')
            ->modalCancelActionLabel('Batal')
            ->action(fn() => $this->mulaiUpdate());
    }

    public function mulaiUpdate(): void
    {
        $kunci = $this->kunciOta();

        try {
            if ($this->statusMesin !== 'ONLINE') {
                throw new \RuntimeException('Perangkat HMI sedang OFFLINE, update tidak bisa dikirim.');
            }

            if (Cache::has($kunci)) {
                throw new \RuntimeException('Update sebelumnya masih berjalan. Mohon tunggu.');
            }

            app(DeviceHealthService::class)->kirimPerintahOta(
                static::$deviceKey,
                $this->newFirmwareVersion
            );

            Cache::put($kunci, [
                'target' => $this->newFirmwareVersion,
                'mulai'  => time(),
            ], self::OTA_BATAS_DETIK + 60);

            $this->periksaStatusOta(null);

            Notification::make()
                ->title('Perintah update terkirim')
                ->body('Menunggu HMI mengunduh dan memasang firmware.')
                ->info()
                ->send();
        } catch (\Exception $e) {
            Notification::make()->title('Gagal mengirim update')->danger()->body($e->getMessage())->send();
        }
    }

    /**
     * Menentukan update masih berjalan, sukses, atau gagal.
     * $laporan = pesan terakhir dari topik "ota" milik HMI (boleh null).
     */
    private function periksaStatusOta(?array $laporan): void
    {
        $this->otaBerjalan = false;
        $this->otaPersen = null;
        $this->otaPesan = '';

        $kunci = $this->kunciOta();
        $pending = Cache::get($kunci);

        if (! $pending) {
            return;
        }

        $lama = time() - (int) $pending['mulai'];

        // 1. Sukses: HMI sudah melapor versi target.
        if (
            $this->firmwareVersion !== '-'
            && ltrim($this->firmwareVersion, 'vV') === ltrim($pending['target'], 'vV')
        ) {
            Cache::forget($kunci);

            Notification::make()
                ->title('Firmware berhasil diperbarui')
                ->body("HMI sekarang menjalankan {$this->firmwareVersion}.")
                ->success()
                ->send();

            return;
        }

        // Hanya pakai laporan yang terbit SETELAH tombol ditekan.
        $laporan = ($laporan && ($laporan['at'] ?? 0) >= $pending['mulai']) ? $laporan : null;
        $state = $laporan['state'] ?? null;

        // 2. HMI melapor gagal.
        if ($state === 'failed') {
            Cache::forget($kunci);

            Notification::make()
                ->title('Update gagal di HMI')
                ->body($laporan['msg'] ?? 'HMI melaporkan kegagalan saat memperbarui.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        // 3. HMI merasa sudah versi terbaru.
        if ($state === 'uptodate') {
            Cache::forget($kunci);

            Notification::make()
                ->title('HMI menganggap firmware sudah terbaru')
                ->body('Periksa apakah versi di firmware (FW_VERSION) sudah sesuai tag rilis.')
                ->warning()
                ->send();

            return;
        }

        // 4. Timeout.
        if ($lama > self::OTA_BATAS_DETIK) {
            Cache::forget($kunci);

            Notification::make()
                ->title('Update belum terkonfirmasi')
                ->body('HMI belum melaporkan versi baru setelah 5 menit. Periksa HMI atau coba lagi.')
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        // 5. Masih berjalan.
        $this->otaBerjalan = true;
        $this->otaDetik = $lama;
        $this->otaTarget = $pending['target'];
        $this->otaPersen = isset($laporan['pct']) ? max(0, min(100, (int) $laporan['pct'])) : null;
        $this->otaPesan = (string) ($laporan['msg'] ?? '');
    }

    private function kunciOta(): string
    {
        return 'ota_pending_' . static::$deviceKey;
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
