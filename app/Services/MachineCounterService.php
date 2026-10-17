<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\MachineResetLog;
use App\Models\ProductionRecap;
use Carbon\Carbon;
use PhpMqtt\Client\Facades\MQTT;

class MachineCounterService
{
    private const HARI_PRODUKSI_MULAI = 5;

    // Alat dianggap TIDAK ADA KABAR jika tidak ada pesan langsung lebih dari ini (detik).
    private const BATAS_OFFLINE_DETIK = 15;

    // Isi topik "status" yang berarti alat pamit / terputus (Last Will).
    // "0" = Sanding Besar, "OFFLINE" = Sanding Kecil.
    private const STATUS_OFFLINE = ['0', 'OFFLINE'];

    // Pemantau (mqtt:subscribe) dianggap mati jika denyutnya lebih lama dari ini (detik).
    private const BATAS_PEMANTAU_DETIK = 20;

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

    // -----------------------------------------------------
    // DENYUT PEMANTAU
    // Dipanggil berkala oleh command mqtt:subscribe. Dipakai untuk
    // membedakan "semua alat mati" dari "pemantaunya yang mati".
    // -----------------------------------------------------
    public function catatPemantauHidup(): void
    {
        cache()->put('veneer_pemantau_hidup', now()->timestamp);
    }

    public function pemantauHidup(): bool
    {
        $nilai = cache()->get('veneer_pemantau_hidup');

        // Belum pernah tercatat (misalnya baru deploy): anggap hidup,
        // supaya dashboard tidak langsung menuduh pemantau mati.
        if ($nilai === null) {
            return true;
        }

        return (time() - (int) $nilai) <= self::BATAS_PEMANTAU_DETIK;
    }

    // -----------------------------------------------------
    // PESAN MASUK DARI MQTT
    // $retained = true berarti pesan LAMA yang dikirim ulang broker saat
    // subscriber baru menyala. Itu bukan bukti alat sedang hidup.
    // -----------------------------------------------------
    public function simpanDariMqtt(string $slug, string $jenis, string $pesan, bool $retained = false): void
    {
        // 1. Perintah buatan dashboard sendiri ikut terbaca karena subscribe "pabrik/#".
        //    Itu bukan data alat dan tidak boleh dianggap tanda alat hidup.
        if ($jenis === 'perintah') {
            return;
        }

        // 2. Pesan "pamit" (Last Will atau restart terencana): alat terputus SEKARANG.
        //    last_seen sengaja tidak disentuh, hanya diberi tanda.
        if ($jenis === 'status' && in_array($pesan, self::STATUS_OFFLINE, true)) {
            cache()->put($this->cacheKey($slug, 'terputus'), true);

            if (! $retained) {
                cache()->put($this->cacheKey($slug, 'putus_pada'), now()->timestamp);
            }

            return;
        }

        // 3. Kiriman ulang dari broker: pulihkan nilai terakhir saja.
        //    Tidak menyentuh last_seen, "terakhir berubah", maupun laju produksi.
        if ($retained) {
            $this->terapkanNilai($slug, $jenis, $pesan);

            return;
        }

        // 4. Pesan langsung dari alat = tanda alat hidup.
        $sebelumnya = cache()->get($this->cacheKey($slug, 'last_seen'));
        $terputus = (bool) cache()->get($this->cacheKey($slug, 'terputus'), false);
        $sekarang = now()->timestamp;

        cache()->put($this->cacheKey($slug, 'last_seen'), $sekarang);
        cache()->forget($this->cacheKey($slug, 'terputus'));

        // Perangkat kembali setelah senyap atau setelah terputus: mulai pengamatan baru.
        $baruPulih = $sebelumnya
            && ((($sekarang - $sebelumnya) > self::BATAS_OFFLINE_DETIK) || $terputus);

        if ($baruPulih) {
            cache()->put($this->cacheKey($slug, 'amati_mulai'), $sekarang);
        }

        // Catat perubahan hitungan SEBELUM nilai lama ditimpa.
        if ($jenis === 'jumlah') {
            $this->catatPerubahan($slug, 's1', (int) $pesan);
        } elseif ($jenis === 'jumlah2') {
            $this->catatPerubahan($slug, 's2', (int) $pesan);
        }

        $this->terapkanNilai($slug, $jenis, $pesan);

        if ($baruPulih && ($machine = Machine::firstWhere('slug', $slug))) {
            try {
                app(MachineEventService::class)->periksa($machine);
            } catch (\Throwable $e) {
                report($e); // jangan sampai listener MQTT ikut mati
            }
        }
    }

    private function terapkanNilai(string $slug, string $jenis, string $pesan): void
    {
        match ($jenis) {
            'jumlah' => cache()->put($this->cacheKey($slug, 'jumlah_s1'), (int) $pesan),
            'jumlah2' => cache()->put($this->cacheKey($slug, 'jumlah_s2'), (int) $pesan),
            'kecepatan' => cache()->put($this->cacheKey($slug, 'kecepatan'), (int) $pesan),
            'ssid' => cache()->put($this->cacheKey($slug, 'ssid'), $pesan),
            's1_error' => cache()->put($this->cacheKey($slug, 's1_error'), $pesan === '1'),
            's2_error' => cache()->put($this->cacheKey($slug, 's2_error'), $pesan === '1'),
            'ip' => cache()->put($this->cacheKey($slug, 'ip'), $pesan),
            'diag' => $this->simpanDiag($slug, $pesan),
            default => null,
        };
    }

    /** Laporan diagnosa dari ESP32 (JSON). Isinya riwayat gangguan, penyebab restart, dll. */
    private function simpanDiag(string $slug, string $pesan): void
    {
        $data = json_decode($pesan, true);

        if (! is_array($data)) {
            return; // bukan JSON valid: abaikan, jangan merusak data lama
        }

        $data['diterima_pada'] = now()->timestamp;

        cache()->put($this->cacheKey($slug, 'diag'), $data);
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

    /**
     * SATU-SATUNYA tempat status alat dihitung untuk tampilan.
     *
     * kondisi:
     *  ONLINE             pesan langsung dari alat masuk < 15 detik lalu
     *                     (berarti LAN dan MQTT pasti OK)
     *  TERPUTUS_MENDADAK  broker memberi tahu alat putus tanpa pamit (Last Will)
     *  TIDAK_ADA_KABAR    alat diam; penyebabnya TIDAK bisa dipastikan dari sini
     *  PEMANTAU_MATI      proses mqtt:subscribe berhenti, status alat tidak bisa dipercaya
     */
    public function bacaStatus(string $slug): array
    {
        $lastSeen = $this->lastSeen($slug);
        $terputus = (bool) cache()->get($this->cacheKey($slug, 'terputus'), false);
        $pemantauHidup = $this->pemantauHidup();

        $online = $pemantauHidup
            && $lastSeen
            && ! $terputus
            && (time() - $lastSeen) <= self::BATAS_OFFLINE_DETIK;

        $kondisi = match (true) {
            ! $pemantauHidup => 'PEMANTAU_MATI',
            $online => 'ONLINE',
            $terputus => 'TERPUTUS_MENDADAK',
            default => 'TIDAK_ADA_KABAR',
        };

        $putusPada = cache()->get($this->cacheKey($slug, 'putus_pada'));

        return [
            'jumlah_s1' => $this->jumlahS1($slug),
            'jumlah_s2' => $this->jumlahS2($slug),
            'online' => $online,
            'kondisi' => $kondisi,
            'terakhir_terlihat' => $lastSeen,
            'putus_pada' => $putusPada ? (int) $putusPada : null,
            'diag' => cache()->get($this->cacheKey($slug, 'diag')),
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

    public function snapshotPerJam(Machine $machine): ?ProductionRecap
    {
        $slug = $machine->slug;

        // Foto diambil di menit 0, jadi yang dicatat adalah jam yang baru selesai.
        $waktu = now()->subHour();
        $tanggal = $waktu->toDateString();
        $jam = (int) $waktu->format('H');

        $s1 = $this->jumlahS1($slug);
        $s2 = $this->jumlahS2($slug);

        // Baris terakhir yang tercatat milik mesin ini (patokan perhitungan).
        $sebelumnya = ProductionRecap::where('machine_id', $machine->id)
            ->where(function ($q) use ($tanggal, $jam) {
                $q->whereDate('tanggal', '<', $tanggal)
                    ->orWhere(fn($q2) => $q2->whereDate('tanggal', $tanggal)->where('jam', '<', $jam));
            })
            ->orderByDesc('tanggal')->orderByDesc('jam')
            ->first();

        // Jendela reset: dari akhir baris terakhir sampai akhir jam ini,
        // supaya reset di jam yang dilewati (mesin diam) tetap terhitung.
        $awalJendela = $sebelumnya
            ? $sebelumnya->tanggal->copy()->setTime((int) $sebelumnya->jam, 0)->addHour()
            : $waktu->copy()->startOfHour();

        $reset = MachineResetLog::where('machine_id', $machine->id)
            ->whereBetween('created_at', [$awalJendela, $waktu->copy()->endOfHour()])
            ->selectRaw('COALESCE(SUM(jumlah_s1_sebelum_reset),0) as s1, COALESCE(SUM(jumlah_s2_sebelum_reset),0) as s2')
            ->first();

        $produksiS1 = $this->hitungProduksi($s1, $sebelumnya?->sensor1_masuk, (int) $reset->s1);
        $produksiS2 = $this->hitungProduksi($s2, $sebelumnya?->sensor2_keluar, (int) $reset->s2);

        // Mesin tidak bergerak: jangan direkap.
        if ($produksiS1 === 0 && $produksiS2 === 0) {
            return null;
        }

        return ProductionRecap::updateOrCreate(
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

    /**
     * Produksi = counter sekarang - counter patokan + angka yang hilang karena reset.
     * Jika hasilnya negatif tanpa ada log reset, perangkat restart: hitung dari 0.
     */
    private function hitungProduksi(int $sekarang, ?int $patokan, int $hilangKarenaReset): int
    {
        if ($patokan === null) {
            return $sekarang + $hilangKarenaReset;
        }

        $selisih = $sekarang - $patokan + $hilangKarenaReset;

        return $selisih >= 0 ? $selisih : $sekarang;
    }
}
