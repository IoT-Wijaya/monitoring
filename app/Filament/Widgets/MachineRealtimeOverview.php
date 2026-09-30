<?php

namespace App\Filament\Widgets;

use App\Models\Machine;
use App\Services\MachineCounterService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

/**
 * Base class widget monitoring realtime. Jangan didaftarkan langsung;
 * daftarkan subclass-nya (SandingRealtimeOverview, CncRealtimeOverview, dst).
 * Setiap subclass cukup mengisi slug dan (opsional) label sensor.
 */
abstract class MachineRealtimeOverview extends Widget
{
    protected string $view = 'filament.widgets.machine-realtime-overview';

    // Polling sudah ditangani wire:poll.1s di Blade, jadi yang bawaan dimatikan.
    protected static ?string $pollingInterval = null;

    // ---- Diisi oleh subclass ----
    protected static string $machineSlug = '';
    protected static string $judulSensor1 = 'Hasil';   // Sensor 1
    protected static string $judulSensor2 = 'Modal';   // Sensor 2
    protected static string $satuanHitung = 'pcs';

    // ---- State yang dipakai Blade ----
    public string $machineNama = '-';
    public string $labelSensor1 = '';
    public string $labelSensor2 = '';
    public string $satuan = 'pcs';

    public int $totalSensor1 = 0;
    public int $totalSensor2 = 0;
    public string $statusMesin = 'OFFLINE';
    public int $kecepatanSinyal = 0;
    public string $wifiSSID = '-';
    public bool $sensor1Error = false;
    public bool $sensor2Error = false;

    public function mount(): void
    {
        if (static::$machineSlug === '') {
            throw new \RuntimeException(static::class . ' belum mengisi $machineSlug.');
        }

        $this->machineNama = Machine::firstWhere('slug', static::$machineSlug)?->nama ?? static::$machineSlug;
        $this->labelSensor1 = static::$judulSensor1;
        $this->labelSensor2 = static::$judulSensor2;
        $this->satuan = static::$satuanHitung;

        $this->updateData();
    }

    public function updateData(): void
    {
        $status = app(MachineCounterService::class)->bacaStatus(static::$machineSlug);

        $this->totalSensor1 = $status['jumlah_s1'];
        $this->totalSensor2 = $status['jumlah_s2'];
        $this->statusMesin = $status['online'] ? 'ONLINE' : 'OFFLINE';
        $this->kecepatanSinyal = $status['kecepatan'];
        $this->wifiSSID = $status['ssid'];
        $this->sensor1Error = $status['s1_error'];
        $this->sensor2Error = $status['s2_error'];
    }

    public function resetCounter(string $target = 'all'): void
    {
        try {
            $machine = Machine::firstWhere('slug', static::$machineSlug);

            if (! $machine) {
                throw new \RuntimeException("Mesin '" . static::$machineSlug . "' tidak ditemukan.");
            }

            app(MachineCounterService::class)->reset($machine, $target);

            Notification::make()->title('Perintah Reset Terkirim')->success()->send();

            $this->updateData();
        } catch (\Exception $e) {
            Notification::make()->title('Gagal Mengirim Perintah')->danger()->body($e->getMessage())->send();
        }
    }

    public function navigateToRekap(): void
    {
        $this->redirect(route('filament.admin.pages.rekapitulasi-harian', [
            'machine' => static::$machineSlug,
        ]));
    }
}
