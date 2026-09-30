<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\MachineResetLog;
use App\Models\ProductionRecap;
use Carbon\Carbon;
use PhpMqtt\Client\Facades\MQTT;

class MachineCounterService
{
    private function cacheKey(string $slug, string $field): string
    {
        return "veneer_{$slug}_{$field}";
    }

    public function jumlahS1(string $slug): int
    {
        return (int) cache()->get($this->cacheKey($slug, 'jumlah_s1'), 0);
    }

    public function jumlahS2(string $slug): int
    {
        return (int) cache()->get($this->cacheKey($slug, 'jumlah_s2'), 0);
    }

    public function lastSeen(string $slug): ?int
    {
        $nilai = cache()->get($this->cacheKey($slug, 'last_seen'));

        return $nilai ? (int) $nilai : null;
    }

    public function simpanDariMqtt(string $slug, string $jenis, string $pesan): void
    {
        $sebelumnya = cache()->get($this->cacheKey($slug, 'last_seen'));
        $sekarang = now()->timestamp;
        cache()->put($this->cacheKey($slug, 'last_seen'), $sekarang);

        // Perangkat kembali setelah senyap: mulai pengamatan baru.
        $baruPulih = $sebelumnya && ($sekarang - $sebelumnya) > 15;
        if ($baruPulih) {
            cache()->put($this->cacheKey($slug, 'amati_mulai'), $sekarang);
        }

        // Catat perubahan hitungan SEBELUM nilai lama ditimpa.
        if ($jenis === 'jumlah') {
            $this->catatPerubahan($slug, 's1', (int) $pesan);
        } elseif ($jenis === 'jumlah2') {
            $this->catatPerubahan($slug, 's2', (int) $pesan);
        }

        match ($jenis) {
            'jumlah' => cache()->put($this->cacheKey($slug, 'jumlah_s1'), (int) $pesan),
            'jumlah2' => cache()->put($this->cacheKey($slug, 'jumlah_s2'), (int) $pesan),
            'kecepatan' => cache()->put($this->cacheKey($slug, 'kecepatan'), (int) $pesan),
            'ssid' => cache()->put($this->cacheKey($slug, 'ssid'), $pesan),
            's1_error' => cache()->put($this->cacheKey($slug, 's1_error'), $pesan === '1'),
            's2_error' => cache()->put($this->cacheKey($slug, 's2_error'), $pesan === '1'),
            default => null,
        };

        if ($baruPulih && ($machine = Machine::firstWhere('slug', $slug))) {
            try {
                app(MachineEventService::class)->periksa($machine);
            } catch (\Throwable $e) {
                report($e); // jangan sampai listener MQTT ikut mati
            }
        }
    }

    private function catatPerubahan(string $slug, string $sensor, int $baru): void
    {
        $kunciJumlah = $this->cacheKey($slug, "jumlah_{$sensor}");
        $adaSebelumnya = cache()->has($kunciJumlah);
        $lama = (int) cache()->get($kunciJumlah, 0);

        if ($baru === $lama) {
            return;
        }

        cache()->put($this->cacheKey($slug, "{$sensor}_berubah"), now()->timestamp);

        // Selisih tepat setelah perangkat kembali/direset, atau sampel pertama setelah cache kosong,
        // adalah kiriman ulang, bukan laju nyata: jangan dihitung sebagai lonjakan.
        $mulai = $this->mulaiAmati($slug);
        $abaikanLaju = ! $adaSebelumnya || ($mulai && (now()->timestamp - $mulai) <= 30);

        if ($baru > $lama && ! $abaikanLaju) {
            $kunci = $this->cacheKey($slug, "{$sensor}_laju_" . now()->format('YmdHi'));
            cache()->add($kunci, 0, 1800);
            cache()->increment($kunci, $baru - $lama);
        }
    }

    public function terakhirBerubah(string $slug, string $sensor): ?int
    {
        $nilai = cache()->get($this->cacheKey($slug, "{$sensor}_berubah"));

        return $nilai ? (int) $nilai : null;
    }

    public function mulaiAmati(string $slug): ?int
    {
        $nilai = cache()->get($this->cacheKey($slug, 'amati_mulai'));

        return $nilai ? (int) $nilai : null;
    }

    /** Hitungan per menit untuk menit ke-$dari sampai ke-$sampai sebelum sekarang (1 = menit lengkap terakhir). */
    public function lajuMenit(string $slug, string $sensor, int $dari, int $sampai): array
    {
        $kunci = [];
        for ($i = $dari; $i <= $sampai; $i++) {
            $kunci[$i] = $this->cacheKey($slug, "{$sensor}_laju_" . now()->subMinutes($i)->format('YmdHi'));
        }

        $nilai = cache()->many(array_values($kunci));

        return array_map(fn($k) => (int) ($nilai[$k] ?? 0), $kunci);
    }

    public function bacaStatus(string $slug): array
    {
        $lastSeen = cache()->get($this->cacheKey($slug, 'last_seen'));
        $online = $lastSeen && (time() - $lastSeen) <= 15;

        return [
            'jumlah_s1' => $this->jumlahS1($slug),
            'jumlah_s2' => $this->jumlahS2($slug),
            'online' => $online,
            'kecepatan' => $online ? (int) cache()->get($this->cacheKey($slug, 'kecepatan'), 0) : 0,
            'ssid' => $online ? cache()->get($this->cacheKey($slug, 'ssid'), 'Unknown WiFi') : 'Disconnected',
            's1_error' => $online && (bool) cache()->get($this->cacheKey($slug, 's1_error'), false),
            's2_error' => $online && (bool) cache()->get($this->cacheKey($slug, 's2_error'), false),
        ];
    }

    public function reset(Machine $machine, string $target = 'all'): void
    {
        $slug = $machine->slug;
        $ubahS1 = in_array($target, ['all', 'sensor1'], true);
        $ubahS2 = in_array($target, ['all', 'sensor2'], true);

        // 1. Catat sebagai LOG AKTIVITAS, bukan rekap produksi lagi.
        MachineResetLog::create([
            'machine_id' => $machine->id,
            'user_id' => auth()->id(),
            'target' => $target,
            'jumlah_s1_sebelum_reset' => $ubahS1 ? $this->jumlahS1($slug) : 0,
            'jumlah_s2_sebelum_reset' => $ubahS2 ? $this->jumlahS2($slug) : 0,
        ]);

        // 2. Nolkan cache -> dashboard langsung update tanpa menunggu ESP32.
        if ($ubahS1) {
            cache()->put($this->cacheKey($slug, 'jumlah_s1'), 0);
        }
        if ($ubahS2) {
            cache()->put($this->cacheKey($slug, 'jumlah_s2'), 0);
        }

        // 3. Kirim perintah fisik ke device.
        $command = match ($target) {
            'sensor1' => 'RESET_S1',
            'sensor2' => 'RESET_S2',
            default => 'RESET_ALL',
        };

        $mqtt = MQTT::connection();
        $mqtt->publish("pabrik/{$slug}/perintah", $command, 0);
        $mqtt->disconnect();
        cache()->put($this->cacheKey($slug, 'amati_mulai'), now()->timestamp);
    }

    public function shiftSaatIni(?Carbon $waktu = null): string
    {
        $jam = (int) ($waktu ?? now())->format('H');

        return ($jam >= 6 && $jam < 18) ? 'Shift Pagi' : 'Shift Malam';
    }

    public function snapshotPerJam(Machine $machine): void
    {
        $slug = $machine->slug;

        // Foto diambil di menit 0, jadi yang dicatat adalah jam yang baru selesai.
        $waktu = now()->subHour();
        $tanggal = $waktu->toDateString();
        $jam = (int) $waktu->format('H');

        $s1 = $this->jumlahS1($slug);
        $s2 = $this->jumlahS2($slug);

        // Foto sebelumnya milik mesin yang sama.
        $sebelumnya = ProductionRecap::where('machine_id', $machine->id)
            ->where(function ($q) use ($tanggal, $jam) {
                $q->whereDate('tanggal', '<', $tanggal)
                    ->orWhere(fn($q2) => $q2->whereDate('tanggal', $tanggal)->where('jam', '<', $jam));
            })
            ->orderByDesc('tanggal')->orderByDesc('jam')
            ->first();

        // Angka yang hilang karena reset selama jam tersebut.
        $reset = MachineResetLog::where('machine_id', $machine->id)
            ->whereBetween('created_at', [$waktu->copy()->startOfHour(), $waktu->copy()->endOfHour()])
            ->selectRaw('COALESCE(SUM(jumlah_s1_sebelum_reset),0) as s1, COALESCE(SUM(jumlah_s2_sebelum_reset),0) as s2')
            ->first();

        $produksiS1 = max(0, $s1 - ($sebelumnya->sensor1_masuk ?? 0) + (int) $reset->s1);
        $produksiS2 = max(0, $s2 - ($sebelumnya->sensor2_keluar ?? 0) + (int) $reset->s2);

        ProductionRecap::updateOrCreate(
            ['machine_id' => $machine->id, 'tanggal' => $tanggal, 'jam' => $jam],
            [
                'shift' => $this->shiftSaatIni($waktu),
                'sensor1_masuk' => $s1,
                'sensor2_keluar' => $s2,
                'produksi_s1' => $produksiS1,
                'produksi_s2' => $produksiS2,
            ]
        );
    }
}
