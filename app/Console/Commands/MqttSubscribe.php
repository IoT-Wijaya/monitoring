<?php

namespace App\Console\Commands;

use App\Models\VeneerLog;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Mendengarkan data MQTT dari ESP32';

    public function handle()
    {
        $mqtt = MQTT::connection();
        $this->info("Terhubung ke broker MQTT...");

        // Subscribe ke semua topik yang diawali pabrik/veneer/
        $mqtt->subscribe('pabrik/veneer/#', function (string $topic, string $message) {

            // 1. UPDATE TIMESTAMP SETIAP PESAN APAPUN MASUK
            cache()->put('veneer_last_seen', now()->timestamp);

            // 2. TAMPILKAN LOG DI TERMINAL
            $this->info("[MQTT IN] Topik: {$topic} | Pesan: {$message}");

            // 3. PROSES DATA KECEPATAN / SINYAL
            if (str_ends_with($topic, 'kecepatan')) {
                cache()->put('veneer_kecepatan', (int) $message);
            }

            // 4. PROSES DATA SSID WIFI (TAMBAHAN)
            if (str_ends_with($topic, 'ssid')) {
                cache()->put('veneer_ssid', $message);
            }

            // 5. PROSES DATA JUMLAH
            if (str_ends_with($topic, 'jumlah')) {
                $jumlah = (int) $message;
                VeneerLog::create([
                    'jumlah' => $jumlah,
                    'kecepatan_mbps' => (int) cache()->get('veneer_kecepatan', 0),
                    'created_at' => now(),
                ]);
            }
        });

        $mqtt->loop();
    }
}
