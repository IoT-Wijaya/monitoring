<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\MachineEvent;
use Carbon\Carbon;

/**
 * Mencatat PERUBAHAN kondisi mesin ke tabel machine_events yang sudah ada
 * (jenis: jaringan|sensor1|sensor2, status: normal|gangguan).
 * Satu baris ditulis hanya saat kondisi berubah dari catatan terakhir.
 */
class MachineEventService
{
    public function __construct(private MachineCounterService $counter) {}

    public function periksa(Machine $machine): void
    {
        // Dipanggil dari scheduler dan listener MQTT; kunci mencegah baris ganda.
        cache()->lock("event_mesin_{$machine->id}", 10)->block(5, fn() => $this->proses($machine));
    }

    private function proses(Machine $machine): void
    {
        $slug = $machine->slug;
        $status = $this->counter->bacaStatus($slug);
        $online = $status['online'];

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

        // Error sensor hanya dinilai saat perangkat online; saat offline nilainya tidak diketahui.
        if ($online) {
            $this->catat($machine, 'sensor1', $status['s1_error'], now(), 'Sensor 1 melaporkan error', 'Sensor 1 kembali normal');
            $this->catat($machine, 'sensor2', $status['s2_error'], now(), 'Sensor 2 melaporkan error', 'Sensor 2 kembali normal');
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

        // Kondisi tidak berubah: tidak ada yang dicatat.
        if ($terakhir->status === $statusSekarang) {
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
