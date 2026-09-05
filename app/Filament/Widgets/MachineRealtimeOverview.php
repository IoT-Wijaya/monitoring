<?php

namespace App\Filament\Widgets;

use App\Models\Machine;
use App\Services\MachineCounterService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

/**
 * Base class. JANGAN didaftarkan langsung ke getWidgets() -- ini abstract
 * secara konsep (biar tidak dobel logic), yang didaftarkan adalah
 * subclass-nya (SandingRealtimeOverview, CncRealtimeOverview, dst) yang
 * cuma berisi 1 baris: isi $machineSlug.
 *
 * Kenapa pakai property static, bukan parameter mount()? Karena widget
 * yang didaftarkan lewat getWidgets() di-instantiate otomatis oleh
 * Filament TANPA argumen -- jadi mount() tidak boleh butuh parameter wajib.
 * Property static aman karena nilainya sudah "menempel" di class sejak
 * didefinisikan, tidak perlu dikirim dari luar.
 */
abstract class MachineRealtimeOverview extends Widget
{
    protected string $view = 'filament.widgets.machine-realtime-overview';
    protected static ?string $pollingInterval = '1s';

    // Diisi ulang oleh subclass, contoh: protected static string $machineSlug = 'sanding';
    protected static string $machineSlug = '';

    public string $machineNama = '-';
    public int $totalSanding = 0;
    public int $totalSanding2 = 0;
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
        $this->updateData();
    }

    public function updateData(): void
    {
        $status = app(MachineCounterService::class)->bacaStatus(static::$machineSlug);

        $this->totalSanding = $status['jumlah_s1'];
        $this->totalSanding2 = $status['jumlah_s2'];
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
