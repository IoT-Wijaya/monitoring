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

        $mqtt->subscribe('pabrik/veneer/#', function (string $topic, string $message) {

            // 1. UPDATE TIMESTAMP SETIAP PESAN MASUK
            cache()->put('veneer_last_seen', now()->timestamp);

            $this->info("[MQTT IN] Topik: {$topic} | Pesan: {$message}");

            // 2. PROSES DATA KECEPATAN & SSID
            if (str_ends_with($topic, 'kecepatan')) {
                cache()->put('veneer_kecepatan', (int) $message);
            }
            if (str_ends_with($topic, 'ssid')) {
                cache()->put('veneer_ssid', $message);
            }

            // 3. TANGKAP STATUS ERROR HARDWARE DARI ESP32
            if ($topic === 'pabrik/veneer/s1_error') {
                cache()->put('veneer_s1_error', $message === '1');
            }
            if ($topic === 'pabrik/veneer/s2_error') {
                cache()->put('veneer_s2_error', $message === '1');
            }

            // 4. SIMPAN STATE SENSOR KE CACHE AGAR TIDAK BENTROK
            if ($topic === 'pabrik/veneer/jumlah') {
                cache()->put('veneer_jumlah_s1', (int) $message);
            }
            if ($topic === 'pabrik/veneer/jumlah2') {
                cache()->put('veneer_jumlah_s2', (int) $message);
            }

            // 5. SIMPAN KE DATABASE SEBAGAI SATU KESATUAN (UPDATE OR CREATE)
            $jumlah1 = cache()->get('veneer_jumlah_s1', 0);
            $jumlah2 = cache()->get('veneer_jumlah_s2', 0);
            $kecepatan = cache()->get('veneer_kecepatan', 0);

            VeneerLog::updateOrCreate(
                ['created_at' => today()],
                [
                    'jumlah' => $jumlah1,
                    'jumlah2' => $jumlah2,
                    'kecepatan_mbps' => $kecepatan,
                ]
            );
        });

        $mqtt->loop();
    }
}
