<?php

namespace App\Console\Commands;

use App\Models\Machine;
use App\Services\DeviceHealthService;
use App\Services\MachineCounterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use PhpMqtt\Client\Facades\MQTT;
use Throwable;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Mendengarkan data MQTT dari semua ESP32 (multi-mesin) dan kesehatan perangkat HMI';

    public function handle(MachineCounterService $counter, DeviceHealthService $health): int
    {
        // TITIK 3: Bungkus seluruh proses koneksi & loop untuk menangkap error fatal (broker/DB down)
        try {
            $validSlugs = Machine::pluck('slug')->all();

            $perangkatHmi = [
                'hmi_sanding' => 'sanding-hmi',
            ];
            $jenisHmi = ['status', 'ip', 'diag', 'ota'];

            $mqtt = MQTT::connection();
            $this->info('Terhubung ke broker MQTT...');

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

            $mqtt->subscribe('pabrik/#', function (string $topic, string $message, bool $retained = false) use ($counter, $health, $validSlugs, $perangkatHmi, $jenisHmi) {
                // TITIK 1: Tangkap error per pesan masuk agar 1 pesan rusak tidak mematikan seluruh worker
                try {
                    $segments = explode('/', $topic);

                    if (count($segments) < 3) {
                        return;
                    }

                    [, $slug, $jenis] = $segments;

                    // Jalur kesehatan perangkat HMI
                    if (isset($perangkatHmi[$slug])) {
                        $deviceKey = $perangkatHmi[$slug];

                        if ($jenis === 'aktif') {
                            $this->simpanItemAktif($deviceKey, $message);
                            return;
                        }

                        if ($jenis === 'status' && trim($message) === '0') {
                            Cache::forget("hmi:{$deviceKey}:aktif");
                        }

                        if (in_array($jenis, $jenisHmi, true)) {
                            $this->info("[HMI] {$slug}/{$jenis}" . ($retained ? ' (retained)' : ''));
                            $health->simpanPesan($deviceKey, $jenis, $message, $retained);
                        }
                        return;
                    }

                    // Lewati diam-diam kalau slug bukan milik mesin ESP32 terdaftar
                    if (! in_array($slug, $validSlugs, true)) {
                        return;
                    }

                    $tanda = $retained ? ' (retained)' : '';
                    $this->info("[MQTT IN] mesin={$slug} jenis={$jenis} pesan={$message}{$tanda}");

                    $counter->simpanDariMqtt($slug, $jenis, $message, $retained);
                } catch (Throwable $e) {
                    $this->laporSentry($e, 'mqtt_message_error', [
                        'topic' => $topic,
                        'payload' => $message,
                        'retained' => $retained,
                    ]);
                    $this->error("[MQTT ERROR] Gagal memproses topik {$topic}: " . $e->getMessage());
                }
            });

            $mqtt->loop();

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->laporSentry($e, 'mqtt_connection_fatal', [
                'broker_host' => config('mqtt-client.connections.default.host'),
            ]);
            $this->error('[MQTT FATAL] Koneksi broker/loop terputus: ' . $e->getMessage());

            // Kembalikan FAILURE agar Docker restart policy otomatis menyalakan ulang worker
            return self::FAILURE;
        }
    }

    private function simpanItemAktif(string $deviceKey, string $message): void
    {
        $key = "hmi:{$deviceKey}:aktif";
        $message = trim($message);

        if ($message === '') {
            Cache::forget($key);
            $this->info('[HMI] aktif: kosong (tidak ada sanding)');
            return;
        }

        $data = json_decode($message, true);
        if (! is_array($data)) {
            $this->warn("[HMI] aktif: JSON tidak valid: {$message}");

            // TITIK 2: Laporkan payload JSON rusak dari HMI ke Sentry sebagai warning
            if (app()->bound('sentry')) {
                \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($deviceKey, $message) {
                    $scope->setTag('service', 'mqtt-worker');
                    $scope->setLevel(\Sentry\Severity::warning());
                    $scope->setContext('hmi_payload', [
                        'device_key' => $deviceKey,
                        'raw_message' => $message,
                    ]);
                    \Sentry\captureMessage("HMI [{$deviceKey}] mengirim JSON aktif tidak valid");
                });
                \Sentry\SentrySdk::getCurrentHub()->getClient()?->flush();
            }
            return;
        }

        Cache::put($key, $data, now()->addHours(12));
        $this->info('[HMI] aktif: ' . ($data['barang'] ?? '-') . ' / ' . ($data['sisi'] ?? '-'));
    }

    /**
     * Helper khusus untuk mengirim error MQTT ke Sentry secara instan (real-time)
     */
    private function laporSentry(Throwable $e, string $errorType, array $contextData = []): void
    {
        if (! app()->bound('sentry')) {
            return;
        }

        \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($e, $errorType, $contextData) {
            $scope->setTag('service', 'mqtt-worker');
            $scope->setTag('mqtt_error_type', $errorType);
            $scope->setContext('mqtt_detail', $contextData);
            \Sentry\captureException($e);
        });

        // Wajib flush() karena worker berjalan terus menerus (long-running process)
        \Sentry\SentrySdk::getCurrentHub()->getClient()?->flush();
    }
}
