<?php

namespace App\Console\Commands;

use App\Models\Machine;
use App\Services\MachineCounterService;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Mendengarkan data MQTT dari semua ESP32 (multi-mesin)';

    public function handle(MachineCounterService $counter): int
    {
        // Ambil semua slug mesin yang terdaftar di DB, jadi whitelist.
        // Topic dari sistem lain (misal PLC vibrasi) yang berbagi
        // prefix "pabrik/" tapi slug-nya tidak terdaftar akan diabaikan.
        //
        // PENTING: slug harus cocok dengan topik di ESP32.
        //   Sanding Besar -> pabrik/veneer/...  -> slug "veneer"
        //   Sanding Kecil -> pabrik/sanding/... -> slug "sanding"
        // Slug yang belum terdaftar di tabel machines diabaikan DIAM-DIAM.
        // Daftar ini dibaca sekali saat start: mesin baru butuh restart subscriber.
        $validSlugs = Machine::pluck('slug')->all();

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
        $mqtt->subscribe('pabrik/#', function (string $topic, string $message, bool $retained = false) use ($counter, $validSlugs) {
            $segments = explode('/', $topic);

            if (count($segments) < 3) {
                return;
            }

            [, $slug, $jenis] = $segments;

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
