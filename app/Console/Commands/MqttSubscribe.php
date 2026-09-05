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
        $validSlugs = Machine::pluck('slug')->all();

        $mqtt = MQTT::connection();
        $this->info('Terhubung ke broker MQTT...');

        $mqtt->subscribe('pabrik/#', function (string $topic, string $message) use ($counter, $validSlugs) {
            $segments = explode('/', $topic);

            if (count($segments) < 3) {
                return;
            }

            [, $slug, $jenis] = $segments;

            // Lewati diam-diam kalau slug bukan milik mesin ESP32 terdaftar.
            if (! in_array($slug, $validSlugs, true)) {
                return;
            }

            $this->info("[MQTT IN] mesin={$slug} jenis={$jenis} pesan={$message}");

            $counter->simpanDariMqtt($slug, $jenis, $message);
        });

        $mqtt->loop();

        return self::SUCCESS;
    }
}
