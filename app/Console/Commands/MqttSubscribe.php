<?php

namespace App\Console\Commands;

use App\Models\Machine;
use App\Services\DeviceHealthService;
use App\Services\MachineCounterService;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Mendengarkan data MQTT dari semua ESP32 (multi-mesin) dan kesehatan perangkat HMI';

    public function handle(MachineCounterService $counter, DeviceHealthService $health): int
    {
        // Whitelist mesin penghitung: slug harus cocok dengan topik di ESP32.
        //   Sanding Besar -> pabrik/veneer/...  -> slug "veneer"
        //   Sanding Kecil -> pabrik/sanding/... -> slug "sanding"
        // Slug yang belum terdaftar di tabel machines diabaikan DIAM-DIAM.
        // Daftar ini dibaca sekali saat start: mesin baru butuh restart subscriber.
        $validSlugs = Machine::pluck('slug')->all();

        // Perangkat HMI: segmen topik MQTT -> deviceKey di widget/cache.
        // Perangkat ini TIDAK didaftarkan ke tabel machines, karena
        // simpanDariMqtt() akan memperlakukannya sebagai mesin penghitung.
        //   pabrik/hmi_sanding/{status|ip|diag} -> deviceKey "sanding-hmi"
        $perangkatHmi = [
            'hmi_sanding' => 'sanding-hmi',
        ];
        $jenisHmi = ['status', 'ip', 'diag', 'ota'];

        $mqtt = MQTT::connection();
        $this->info('Terhubung ke broker MQTT...');

        // Denyut pemantau: tiap 5 detik menandai "subscriber ini masih hidup".
        // Dashboard memakainya untuk membedakan "semua alat mati" dari
        // "pemantaunya yang mati".
        $counter->catatPemantauHidup();
        $terakhirDenyut = time();

        $mqtt->registerLoopEventHandler(
            function ($client, float $elapsed) use ($counter, &$terakhirDenyut) {
                if (time() - $terakhirDenyut >= 5) {
                    $terakhirDenyut = time();
                    $counter->catatPemantauHidup();
                }
            }
        );

        // $retained = true: pesan LAMA yang dikirim ulang broker saat subscriber
        // baru menyala. Bukan bukti alat sedang hidup, jadi service mengabaikannya
        // untuk status online.
        $mqtt->subscribe('pabrik/#', function (string $topic, string $message, bool $retained = false) use ($counter, $health, $validSlugs, $perangkatHmi, $jenisHmi) {
            $segments = explode('/', $topic);

            if (count($segments) < 3) {
                return;
            }

            [, $slug, $jenis] = $segments;

            // Jalur kesehatan perangkat HMI: dicegat sebelum whitelist mesin.
            if (isset($perangkatHmi[$slug])) {
                if (in_array($jenis, $jenisHmi, true)) {
                    $this->info("[HMI] {$slug}/{$jenis}" . ($retained ? ' (retained)' : ''));
                    $health->simpanPesan($perangkatHmi[$slug], $jenis, $message, $retained);
                }
                return;
            }

            // Lewati diam-diam kalau slug bukan milik mesin ESP32 terdaftar.
            if (! in_array($slug, $validSlugs, true)) {
                return;
            }

            $tanda = $retained ? ' (retained)' : '';
            $this->info("[MQTT IN] mesin={$slug} jenis={$jenis} pesan={$message}{$tanda}");

            $counter->simpanDariMqtt($slug, $jenis, $message, $retained);
        });

        $mqtt->loop();

        return self::SUCCESS;
    }
}
