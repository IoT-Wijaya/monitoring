<?php

namespace App\Filament\Widgets;

use App\Models\Machine;
use App\Models\MachineOtaStatus;
use App\Services\MachineCounterService;
use App\Services\MachineEventService;
use App\Services\OtaCommandService;
use App\Services\OtaVersionService;
use App\Support\Ota\FirmwareStatus;
use App\Support\Ota\OtaCommandBuilder;
use App\Support\Ota\OtaCommandRefused;
use App\Support\Ota\OtaProgress;
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

    // Catatan putaran update terakhir disimpan 24 jam (atau sampai ditutup/diganti).
    private const SIMPAN_PUTARAN_DETIK = 86400;

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
    public ?string $menungguVersi = null; // versi tujuan selama putaran update belum selesai
    #[Locked]
    public array $putaran = []; // tahap putaran update terakhir, untuk panel progress
    #[Locked]
    public ?string $alasanUpdate = null; // kenapa update tidak bisa dilakukan sekarang
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

    private function kunciPutaran(): string
    {
        return 'ota:putaran:' . static::$machineSlug;
    }

    private function muatStatusFirmware(): void
    {
        $slug = static::$machineSlug;
        $terpasang = app(OtaVersionService::class)->ambil($slug);
        $rilis = app(OtaCommandService::class)->rilisTerbaru($alasanRilis);
        $status = FirmwareStatus::tentukan($terpasang?->build, $rilis?->build);

        $this->versiTerpasang = $terpasang?->version;
        $this->versiTersedia = $rilis?->version;
        $this->statusFirmware = $status->value;
        $this->labelFirmware = $status->label();

        $mesin = Machine::firstWhere('slug', $slug);
        $laporan = $mesin ? MachineOtaStatus::where('machine_id', $mesin->id)->first() : null;

        $this->muatPutaran($terpasang, $laporan);

        $this->bisaUpdate = $status->bisaUpdate()
            && $this->statusMesin === 'ONLINE'
            && $this->menungguVersi === null;

        $this->alasanUpdate = $this->tentukanAlasanUpdate($status, $terpasang, $rilis, $alasanRilis);
        if ($this->alasanUpdate !== null) {
            $this->bisaUpdate = false;
        }

        $this->hasilOta = $laporan?->result->label();
        $this->kategoriOta = $laporan?->result->kategori() ?? '';
        $this->waktuOta = $laporan?->reported_at?->locale('id')->diffForHumans();
    }

    /**
     * Membaca catatan putaran update terakhir dan menentukan tahapnya (lihat OtaProgress).
     * Mencatat sekali bila mesin terlihat offline, karena itu tanda ia sedang memasang firmware.
     */
    private function muatPutaran(?\App\Support\Ota\FirmwareVersion $terpasang, ?MachineOtaStatus $laporan): void
    {
        $this->putaran = [];
        $this->menungguVersi = null;

        $catatan = Cache::get($this->kunciPutaran());
        if (! is_array($catatan) || ! isset($catatan['build'], $catatan['at'], $catatan['versi'])) {
            return;
        }

        $online = $this->statusMesin === 'ONLINE';
        if (! $online && empty($catatan['offline'])) {
            $catatan['offline'] = true;
            Cache::put($this->kunciPutaran(), $catatan, self::SIMPAN_PUTARAN_DETIK);
        }

        $fase = OtaProgress::tentukan(
            (int) $catatan['build'],
            (int) $catatan['at'],
            time(),
            ! empty($catatan['offline']),
            $online,
            $terpasang?->build,
            $laporan?->result,
            $laporan?->target_build,
            $laporan?->reported_at?->timestamp
        );

        $this->putaran = [
            'aktif' => true,
            'versi' => (string) $catatan['versi'],
            'fase' => $fase->value,
            'judul' => $fase->judul(),
            'keterangan' => $fase->keterangan(),
            'persen' => $fase->persen(),
            'nada' => $fase->nada(),
            'langkah' => $fase->langkahAktif(),
            'selesai' => $fase->selesai(),
            'dikirim' => Carbon::createFromTimestamp((int) $catatan['at'])->locale('id')->diffForHumans(),
        ];
        $this->menungguVersi = $fase->selesai() ? null : (string) $catatan['versi'];
    }

    /**
     * Alasan update tidak bisa dilakukan sekarang, atau null bila boleh atau tidak relevan.
     * Memakai aturan yang sama dengan perintah sungguhan (OtaCommandBuilder), jadi tombol
     * tidak aktif untuk sesuatu yang pasti ditolak.
     */
    private function tentukanAlasanUpdate(
        FirmwareStatus $status,
        ?\App\Support\Ota\FirmwareVersion $terpasang,
        ?\App\Support\Ota\OtaRelease $rilis,
        ?string $alasanRilis
    ): ?string {
        if ($status === FirmwareStatus::TanpaRilis) {
            return $alasanRilis ?? 'Belum ada rilis resmi.';
        }
        if ($status !== FirmwareStatus::TersediaUpdate || $terpasang === null || $rilis === null) {
            return null; // Terbaru, Tidak diketahui, dan sejenisnya sudah dijelaskan oleh label status
        }
        if ($this->menungguVersi !== null) {
            return 'Perintah update sebelumnya masih ditunggu.';
        }
        if ($this->statusMesin !== 'ONLINE') {
            return 'Mesin sedang offline; perintah tidak bisa dikirim.';
        }

        try {
            OtaCommandBuilder::build(
                static::$machineSlug,
                (string) config('ota.device_type'),
                $terpasang->build,
                $rilis
            );
        } catch (OtaCommandRefused $e) {
            return $e->getMessage();
        }

        return null;
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

        // Kunci singkat: klik ganda atau dua operator tidak bisa mengirim dua perintah bersamaan.
        $kunci = Cache::lock('ota:kirim:' . static::$machineSlug, 15);

        try {
            if (! $kunci->get()) {
                throw new \RuntimeException('Perintah update sedang diproses.');
            }

            // Jangan percaya state dari browser: hitung ulang dari server.
            $this->updateData();

            if (! $this->bisaUpdate) {
                throw new \RuntimeException($this->alasanUpdate ?? 'Update tidak tersedia saat ini.');
            }

            $mesin = Machine::firstWhere('slug', static::$machineSlug)
                ?? throw new \RuntimeException("Mesin '" . static::$machineSlug . "' tidak ditemukan.");

            $terpasang = app(OtaVersionService::class)->ambil(static::$machineSlug)
                ?? throw new \RuntimeException('Versi firmware mesin belum diketahui.');

            $layanan = app(OtaCommandService::class);
            $rilis = $layanan->rilisTerbaru($alasan)
                ?? throw new \RuntimeException($alasan ?? 'Belum ada rilis resmi.');

            $layanan->kirim($mesin, $terpasang->build);

            // Putaran baru dicatat setelah perintah benar-benar terkirim; ini menimpa putaran lama.
            Cache::put(
                $this->kunciPutaran(),
                ['build' => $rilis->build, 'versi' => $rilis->version, 'at' => time(), 'offline' => false],
                self::SIMPAN_PUTARAN_DETIK
            );

            Notification::make()->title('Perintah Update Terkirim')
                ->body('Pantau tahapnya di kartu ini. Jangan matikan daya mesin.')->success()->send();

            $this->updateData();
        } catch (\RuntimeException $e) { // termasuk OtaCommandRefused
            Notification::make()->title('Update Tidak Dikirim')->danger()->body($e->getMessage())->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Gagal Mengirim Perintah Update')->danger()
                ->body('Penyebab: ' . class_basename($e) . '. Rincian ada di storage/logs/laravel.log.')
                ->send();
        } finally {
            $kunci->release();
        }
    }

    /** Menutup panel hasil update (hanya bila putaran sudah selesai). */
    public function tutupPutaran(): void
    {
        if (! $this->bolehUpdateFirmware()) {
            return;
        }

        $this->updateData();

        if (($this->putaran['selesai'] ?? false) === true) {
            Cache::forget($this->kunciPutaran());
            $this->updateData();
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
