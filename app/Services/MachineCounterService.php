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

    public function simpanDariMqtt(string $slug, string $jenis, string $pesan): void
    {
        cache()->put($this->cacheKey($slug, 'last_seen'), now()->timestamp);

        match ($jenis) {
            'jumlah' => cache()->put($this->cacheKey($slug, 'jumlah_s1'), (int) $pesan),
            'jumlah2' => cache()->put($this->cacheKey($slug, 'jumlah_s2'), (int) $pesan),
            'kecepatan' => cache()->put($this->cacheKey($slug, 'kecepatan'), (int) $pesan),
            'ssid' => cache()->put($this->cacheKey($slug, 'ssid'), $pesan),
            's1_error' => cache()->put($this->cacheKey($slug, 's1_error'), $pesan === '1'),
            's2_error' => cache()->put($this->cacheKey($slug, 's2_error'), $pesan === '1'),
            default => null,
        };
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
