<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\MachineEvent;
use Carbon\Carbon;

/**
 * Mencatat PERUBAHAN kondisi mesin ke tabel machine_events
 * (jenis: jaringan|sensor1|sensor2, status: normal|gangguan) dan
 * mendiagnosa perilaku sensor dari data hitungan yang masuk.
 */
class MachineEventService
{
    public function __construct(private MachineCounterService $counter) {}

    /**
     * Dipanggil dari scheduler (tiap 10 detik) dan dari listener MQTT
     * saat perangkat kembali; kunci mencegah baris ganda.
     */
    public function periksa(Machine $machine): void
    {
        cache()->lock("event_mesin_{$machine->id}", 10)->block(5, fn() => $this->proses($machine));
    }

    /**
     * Hasil diagnosa terakhir (dibaca widget tiap detik tanpa menghitung ulang).
     *
     * @return array{s1: ?string, s2: ?string}
     */
    public function diagnosaTersimpan(string $slug): array
    {
        return cache()->get("mesin_diagnosa_{$slug}", ['s1' => null, 's2' => null]);
    }

    /**
     * Kembalikan teks alasan (stabil, tanpa angka yang berubah-ubah) bila sensor
     * perlu dicek, atau null bila normal. Diagnosa berisi fakta pembacaan
     * perangkat, bukan penilaian terhadap orang.
     *
     * @return array{s1: ?string, s2: ?string}
     */
    public function diagnosa(string $slug, array $status): array
    {
        $hasil = ['s1' => null, 's2' => null];

        if (! $status['online']) {
            return $hasil;
        }

        $diamMenit = max(2, (int) config('mesin.diam_menit', 10));
        $ambangLawan = (int) config('mesin.ambang_lawan', 10);
        $batas = (int) (config("mesin.batas_per_menit.{$slug}") ?? config('mesin.batas_per_menit.default', 120));

        $mulaiAmati = $this->counter->mulaiAmati($slug);
        $cukupDiamati = ! $mulaiAmati || (now()->timestamp - $mulaiAmati) >= $diamMenit * 60;

        foreach (['s1' => 's2', 's2' => 's1'] as $sensor => $lawan) {
            $nomor = $sensor === 's1' ? 1 : 2;
            $nomorLawan = 3 - $nomor;

            // 1. Laporan langsung dari perangkat.
            if ($status["{$sensor}_error"]) {
                $hasil[$sensor] = "Sensor {$nomor} melaporkan error (dari perangkat)";
                continue;
            }

            // 2. Lonjakan: hitungan naik melebihi kemampuan fisik mesin (3 menit lengkap terakhir).
            if (max($this->counter->lajuMenit($slug, $sensor, 1, 3)) > $batas) {
                $hasil[$sensor] = "Hitungan Sensor {$nomor} naik melebihi {$batas} per menit "
                    . '(kemungkinan terhitung sendiri atau gangguan sinyal)';
                continue;
            }

            // 3. Diam relatif: sensor ini nol selama jendela penuh, sementara sensor lawan
            //    sudah aktif sejak paruh awal jendela. Bila keduanya diam, itu mesin berhenti, bukan galat.
            if ($cukupDiamati) {
                $sendiri = array_sum($this->counter->lajuMenit($slug, $sensor, 1, $diamMenit));
                $lawanAwal = array_sum($this->counter->lajuMenit($slug, $lawan, (int) ceil($diamMenit / 2), $diamMenit));

                if ($sendiri === 0 && $lawanAwal >= $ambangLawan) {
                    $hasil[$sensor] = "Sensor {$nomor} tidak menghitung selama {$diamMenit} menit "
                        . "sementara Sensor {$nomorLawan} tetap menghitung";
                }
            }
        }

        return $hasil;
    }

    private function proses(Machine $machine): void
    {
        $slug = $machine->slug;
        $status = $this->counter->bacaStatus($slug);
        $online = $status['online'];

        $diagnosa = $this->diagnosa($slug, $status);
        cache()->put("mesin_diagnosa_{$slug}", $diagnosa, 120);

        // Waktu gangguan jaringan = terakhir kali perangkat terlihat, bukan saat terdeteksi.
        $lastSeen = $this->counter->lastSeen($slug);
        $waktuTerputus = $lastSeen ? Carbon::createFromTimestamp($lastSeen) : now();

        $this->catat(
            $machine,
            'jaringan',
            ! $online,
            $online ? now() : $waktuTerputus,
            'LAN atau MQTT terputus (tidak ada data dari perangkat lebih dari 15 detik)',
            'Perangkat kembali online (LAN OK, MQTT OK)'
        );

        // Kondisi sensor hanya dinilai saat perangkat online.
        if ($online) {
            $this->catat($machine, 'sensor1', $diagnosa['s1'] !== null, now(), (string) $diagnosa['s1'], 'Sensor 1 kembali normal');
            $this->catat($machine, 'sensor2', $diagnosa['s2'] !== null, now(), (string) $diagnosa['s2'], 'Sensor 2 kembali normal');
        }
    }

    private function catat(
        Machine $machine,
        string $jenis,
        bool $gangguan,
        Carbon $waktu,
        string $keteranganGangguan,
        string $keteranganNormal
    ): void {
        $terakhir = MachineEvent::query()
            ->where('machine_id', $machine->id)
            ->where('jenis', $jenis)
            ->orderByDesc('terjadi_pada')
            ->orderByDesc('id')
            ->first();

        $statusSekarang = $gangguan ? 'gangguan' : 'normal';

        // Belum ada catatan: hanya catat bila sudah bermasalah.
        if (! $terakhir) {
            if ($gangguan) {
                $this->simpan($machine, $jenis, 'gangguan', $keteranganGangguan, $waktu);
            }

            return;
        }

        if ($terakhir->status === $statusSekarang) {
            // Tidak berubah. Kecuali penyebab gangguan berganti: catat sebagai baris baru.
            if (! $gangguan || $terakhir->keterangan === $keteranganGangguan) {
                return;
            }

            $this->simpan($machine, $jenis, 'gangguan', $keteranganGangguan, $waktu);

            return;
        }

        if ($gangguan) {
            $this->simpan($machine, $jenis, 'gangguan', $keteranganGangguan, $waktu);

            return;
        }

        // Kembali normal: sertakan lama gangguan pada keterangan.
        $detik = (int) abs($terakhir->terjadi_pada->diffInSeconds($waktu));
        $this->simpan($machine, $jenis, 'normal', $keteranganNormal . ' setelah ' . $this->formatDurasi($detik), $waktu);
    }

    private function simpan(Machine $machine, string $jenis, string $status, string $keterangan, Carbon $waktu): void
    {
        MachineEvent::create([
            'machine_id'   => $machine->id,
            'jenis'        => $jenis,
            'status'       => $status,
            'keterangan'   => $keterangan,
            'terjadi_pada' => $waktu,
        ]);
    }

    private function formatDurasi(int $detik): string
    {
        $jam = intdiv($detik, 3600);
        $menit = intdiv($detik % 3600, 60);
        $sisa = $detik % 60;

        $bagian = [];
        if ($jam > 0) {
            $bagian[] = "{$jam} jam";
        }
        if ($menit > 0) {
            $bagian[] = "{$menit} menit";
        }
        if ($sisa > 0 || $bagian === []) {
            $bagian[] = "{$sisa} detik";
        }

        return implode(' ', $bagian);
    }
}
