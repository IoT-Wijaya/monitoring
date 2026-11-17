<?php

namespace App\Filament\Widgets;

use App\Models\Machine;
use App\Models\MachineOtaStatus;
use App\Services\MachineCounterService;
use App\Services\MachineEventService;
use App\Services\OtaCommandService;
use App\Services\OtaVersionService;
use App\Support\Ota\FirmwareStatus;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;

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

    // Lama (detik) perintah update ditunggu sebelum dianggap kedaluwarsa.
    private const MENUNGGU_DETIK = 900;

    // ---- State yang dipakai Blade ----
    public string $machineNama = '-';
    public string $labelSensor1 = '';
    public string $labelSensor2 = '';
    public string $satuan = 'pcs';

    public int $totalSensor1 = 0;
    public int $totalSensor2 = 0;

    // Dikunci: diisi server saja, tidak boleh diubah dari browser.
    #[Locked]
    public string $statusMesin = 'OFFLINE';

    public int $kecepatanSinyal = 0;
    public string $wifiSSID = '-';
    public bool $sensor1Error = false;
    public bool $sensor2Error = false;

    public ?string $infoSensor1 = null;
    public ?string $infoSensor2 = null;
    public string $terakhirSensor1 = '-';
    public string $terakhirSensor2 = '-';

    // ---- State firmware/OTA (semua diisi server) ----
    #[Locked]
    public ?string $versiTerpasang = null;
    #[Locked]
    public ?string $versiTersedia = null;
    #[Locked]
    public string $statusFirmware = 'tidak_diketahui';
    #[Locked]
    public string $labelFirmware = '';
    #[Locked]
    public bool $bisaUpdate = false;
    #[Locked]
    public ?string $menungguVersi = null;
    #[Locked]
    public ?string $hasilOta = null;
    #[Locked]
    public string $kategoriOta = '';
    #[Locked]
    public ?string $waktuOta = null;

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
        $diagnosa = app(MachineEventService::class)->diagnosaTersimpan(static::$machineSlug);
        $counter = app(MachineCounterService::class);
        $this->infoSensor1 = $diagnosa['s1'];
        $this->infoSensor2 = $diagnosa['s2'];
        $this->terakhirSensor1 = $this->formatTerakhir($counter->terakhirBerubah(static::$machineSlug, 's1'));
        $this->terakhirSensor2 = $this->formatTerakhir($counter->terakhirBerubah(static::$machineSlug, 's2'));

        // Harus setelah statusMesin terisi: bisaUpdate bergantung padanya.
        $this->muatStatusFirmware();
    }

    private function kunciMenunggu(): string
    {
        return 'ota:menunggu:' . static::$machineSlug;
    }

    private function muatStatusFirmware(): void
    {
        $slug = static::$machineSlug;
        $terpasang = app(OtaVersionService::class)->ambil($slug);
        $rilis = app(OtaCommandService::class)->rilisTerbaru();
        $status = FirmwareStatus::tentukan($terpasang?->build, $rilis?->build);

        $this->versiTerpasang = $terpasang?->version;
        $this->versiTersedia = $rilis?->version;
        $this->statusFirmware = $status->value;
        $this->labelFirmware = $status->label();

        $mesin = Machine::firstWhere('slug', $slug);
        $laporan = $mesin ? MachineOtaStatus::where('machine_id', $mesin->id)->first() : null;

        // Penantian berakhir bila: mesin sudah melapor build target, ada laporan hasil yang
        // lebih baru dari perintah (berhasil, dikembalikan, atau gagal), atau kedaluwarsa.
        $menunggu = Cache::get($this->kunciMenunggu());
        if (
            is_array($menunggu)
            && (
                ($terpasang !== null && $terpasang->build >= $menunggu['build'])
                || ($laporan?->reported_at?->timestamp ?? 0) > $menunggu['at']
                || time() - $menunggu['at'] > self::MENUNGGU_DETIK
            )
        ) {
            Cache::forget($this->kunciMenunggu());
            $menunggu = null;
        }
        $this->menungguVersi = is_array($menunggu) ? $menunggu['versi'] : null;

        $this->bisaUpdate = $status->bisaUpdate()
            && $this->statusMesin === 'ONLINE'
            && $this->menungguVersi === null;

        $this->hasilOta = $laporan?->result->label();
        $this->kategoriOta = $laporan?->result->kategori() ?? '';
        $this->waktuOta = $laporan?->reported_at?->locale('id')->diffForHumans();
    }

    /**
     * Siapa yang boleh memasang firmware. Bawaan: pengguna yang sudah login.
     * PERKETAT sesuai sistem izin Anda, misalnya: return auth()->user()?->can('update_firmware') ?? false;
     */
    protected function bolehUpdateFirmware(): bool
    {
        return Filament::auth()->check();
    }

    public function kirimUpdate(): void
    {
        if (! $this->bolehUpdateFirmware()) {
            Notification::make()->title('Update Ditolak')->danger()
                ->body('Anda tidak memiliki izin memasang firmware.')->send();

            return;
        }

        try {
            // Jangan percaya state dari browser: hitung ulang dari server.
            $this->updateData();

            if ($this->statusMesin !== 'ONLINE') {
                throw new \RuntimeException('Mesin sedang offline, perintah tidak dikirim.');
            }

            $mesin = Machine::firstWhere('slug', static::$machineSlug)
                ?? throw new \RuntimeException("Mesin '" . static::$machineSlug . "' tidak ditemukan.");

            $terpasang = app(OtaVersionService::class)->ambil(static::$machineSlug)
                ?? throw new \RuntimeException('Versi firmware mesin belum diketahui.');

            $layanan = app(OtaCommandService::class);
            $rilis = $layanan->rilisTerbaru($alasan)
                ?? throw new \RuntimeException($alasan ?? 'Belum ada rilis resmi.');

            // Satu perintah tertunda saja: klik ganda atau dua operator ditolak.
            $dipesan = Cache::add(
                $this->kunciMenunggu(),
                ['build' => $rilis->build, 'versi' => $rilis->version, 'at' => time()],
                self::MENUNGGU_DETIK
            );
            if (! $dipesan) {
                throw new \RuntimeException('Perintah update sebelumnya masih ditunggu.');
            }

            try {
                $layanan->kirim($mesin, $terpasang->build);
            } catch (\Throwable $e) {
                Cache::forget($this->kunciMenunggu()); // gagal kirim: bebaskan reservasi
                throw $e;
            }

            Notification::make()->title('Perintah Update Terkirim')
                ->body('Mesin akan restart dan memasang firmware baru.')->success()->send();

            $this->updateData();
        } catch (\RuntimeException $e) { // termasuk OtaCommandRefused
            Notification::make()->title('Update Tidak Dikirim')->danger()->body($e->getMessage())->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Gagal Mengirim Perintah Update')->danger()->send();
        }
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

    private function formatTerakhir(?int $timestamp): string
    {
        if (! $timestamp) {
            return '-';
        }

        $waktu = Carbon::createFromTimestamp($timestamp)->locale('id');

        return $waktu->format('H:i:s') . ' (' . $waktu->diffForHumans() . ')';
    }
}
