<?php

namespace App\Console\Commands;

use App\Models\Machine;
use App\Services\DeviceHealthService;
use App\Services\MachineCounterService;
use App\Services\MqttLoggingService;
use App\Services\OtaStatusService;
use App\Services\OtaVersionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use PhpMqtt\Client\Facades\MQTT;
use Throwable;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';

    protected $description = 'Mendengarkan data MQTT dari semua ESP32 dan kesehatan HMI';

    public function handle(
        MachineCounterService $counter,
        DeviceHealthService $health,
        MqttLoggingService $logger,
        OtaStatusService $ota,
        OtaVersionService $versiOta
    ): int {
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
                function ($client, float $elapsed) use (
                    $counter,
                    &$terakhirDenyut
                ) {
                    if (time() - $terakhirDenyut >= 5) {
                        $terakhirDenyut = time();
                        $counter->catatPemantauHidup();
                    }
                }
            );

            $mqtt->subscribe(
                'pabrik/#',
                function (
                    string $topic,
                    string $message,
                    bool $retained = false
                ) use (
                    $counter,
                    $health,
                    $logger,
                    $ota,
                    $versiOta,
                    $validSlugs,
                    $perangkatHmi,
                    $jenisHmi
                ) {
                    $slug = null;
                    $jenis = null;
                    $deviceKey = null;

                    try {
                        $segments = explode('/', $topic);

                        if (count($segments) < 3) {
                            return;
                        }

                        [, $slug, $jenis] = $segments;

                        // HMI
                        if (isset($perangkatHmi[$slug])) {
                            $deviceKey = $perangkatHmi[$slug];

                            if ($jenis === 'aktif') {
                                $this->simpanItemAktif(
                                    $deviceKey,
                                    $message,
                                    $logger
                                );

                                return;
                            }

                            if (
                                $jenis === 'status'
                                && trim($message) === '0'
                            ) {
                                Cache::forget("hmi:{$deviceKey}:aktif");
                            }

                            if (in_array($jenis, $jenisHmi, true)) {
                                $this->info(
                                    "[HMI] {$slug}/{$jenis}"
                                        . ($retained ? ' (retained)' : '')
                                );

                                $health->simpanPesan(
                                    $deviceKey,
                                    $jenis,
                                    $message,
                                    $retained
                                );
                            }

                            return;
                        }

                        // Validasi mesin ESP32
                        if (! in_array($slug, $validSlugs, true)) {
                            return;
                        }

                        // Perintah OTA dikirim server sendiri ke perangkat; 'pabrik/#' ikut
                        // menerimanya kembali, dan itu bukan data dari mesin.
                        if ($jenis === 'ota_perintah') {
                            return;
                        }

                        if ($jenis === 'ota_status') {
                            $this->info(
                                "[OTA] mesin={$slug} pesan={$message}"
                                    . ($retained ? ' (retained)' : '')
                            );

                            $ota->catat($slug, $message);

                            return;
                        }

                        $this->info(
                            "[MQTT IN] mesin={$slug} "
                                . "jenis={$jenis} pesan={$message}"
                                . ($retained ? ' (retained)' : '')
                        );

                        if ($jenis === config('ota.jenis_versi')) {
                            $versiOta->catat($slug, $message); // tanpa return: alur lama tetap berjalan
                        }

                        $counter->simpanDariMqtt(
                            $slug,
                            $jenis,
                            $message,
                            $retained
                        );
                    } catch (Throwable $e) {
                        $logger->reportException(
                            $e,
                            'mqtt_message_error',
                            [
                                'topic' => $topic,
                                'machine_slug' => $slug,
                                'message_type' => $jenis,
                                'device_key' => $deviceKey,
                                'payload' => mb_substr($message, 0, 4000),
                                'payload_truncated' => mb_strlen($message) > 4000,
                                'retained' => $retained,
                                'timestamp' => now()->toIso8601String(),
                            ]
                        );

                        $this->error(
                            "[MQTT ERROR] {$topic}: {$e->getMessage()}"
                        );
                    }
                }
            );

            $mqtt->loop();

            return self::SUCCESS;
        } catch (Throwable $e) {
            $logger->reportException(
                $e,
                'mqtt_connection_fatal',
                [
                    'broker_host' => config(
                        'mqtt-client.connections.default.host'
                    ),
                    'environment' => app()->environment(),
                    'php_version' => PHP_VERSION,
                    'timestamp' => now()->toIso8601String(),
                ]
            );

            $this->error(
                '[MQTT FATAL] ' . $e->getMessage()
            );

            return self::FAILURE;
        }
    }

    /**
     * Menyimpan informasi aktivitas HMI.
     */
    private function simpanItemAktif(
        string $deviceKey,
        string $message,
        MqttLoggingService $logger
    ): void {
        $key = "hmi:{$deviceKey}:aktif";

        $message = trim($message);

        if ($message === '') {
            Cache::forget($key);

            $this->info('[HMI] aktif: kosong (tidak ada sanding)');

            return;
        }

        $data = json_decode($message, true);

        if (! is_array($data)) {
            $logger->reportWarning(
                "HMI [{$deviceKey}] mengirim JSON aktif tidak valid",
                'hmi_invalid_json',
                [
                    'device_key' => $deviceKey,
                    'payload' => mb_substr($message, 0, 4000),
                    'payload_truncated' => mb_strlen($message) > 4000,
                    'json_error' => json_last_error_msg(),
                    'timestamp' => now()->toIso8601String(),
                ]
            );

            $this->warn(
                "[HMI] aktif: JSON tidak valid ({$deviceKey})"
            );

            return;
        }

        Cache::put(
            $key,
            $data,
            now()->addHours(12)
        );

        $this->info(
            '[HMI] aktif: '
                . ($data['barang'] ?? '-')
                . ' / '
                . ($data['sisi'] ?? '-')
        );
    }
}
