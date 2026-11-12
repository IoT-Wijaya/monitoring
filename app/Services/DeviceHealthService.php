<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use PhpMqtt\Client\Facades\MQTT;
use RuntimeException;
use Throwable;

class DeviceHealthService
{
    private const BATAS_OFFLINE_DETIK = 15;

    private const TOPIC_PERINTAH = [
        'sanding-hmi' => 'pabrik/hmi_sanding/perintah',
    ];

    public function __construct(
        private readonly MqttLoggingService $logger
    ) {}

    private function kunci(string $deviceKey): string
    {
        return "device:{$deviceKey}:status";
    }

    /**
     * Menerima dan menyimpan informasi kesehatan HMI.
     */
    public function simpanPesan(
        string $deviceKey,
        string $subTopik,
        string $payload,
        bool $retained = false
    ): void {
        $data = Cache::get($this->kunci($deviceKey), []);

        if ($subTopik === 'status') {
            $data['status'] = trim($payload);

            if (! $retained) {
                $data['last_seen'] = time();
            }
        } elseif ($subTopik === 'ip') {
            $data['ip'] = trim($payload);
        } elseif ($subTopik === 'diag') {
            $diag = $this->decodeJson(
                $payload,
                $deviceKey,
                'diag',
                $retained
            );

            if ($diag !== null) {
                $data['diag'] = $diag;
            }
        } elseif ($subTopik === 'ota') {
            if (! $retained) {
                $ota = $this->decodeJson(
                    $payload,
                    $deviceKey,
                    'ota',
                    $retained
                );

                if ($ota !== null) {
                    $ota['at'] = time();
                    $data['ota'] = $ota;
                }
            }
        }

        Cache::forever($this->kunci($deviceKey), $data);
    }

    /**
     * Validasi JSON dan laporkan payload rusak.
     */
    private function decodeJson(
        string $payload,
        string $deviceKey,
        string $subTopik,
        bool $retained
    ): ?array {
        $result = json_decode($payload, true);

        if (! is_array($result)) {
            $this->logger->reportWarning(
                "HMI [{$deviceKey}] mengirim JSON {$subTopik} tidak valid",
                'hmi_invalid_json',
                [
                    'device_key' => $deviceKey,
                    'topic_type' => $subTopik,
                    'payload' => mb_substr($payload, 0, 4000),
                    'payload_truncated' => mb_strlen($payload) > 4000,
                    'json_error' => json_last_error_msg(),
                    'retained' => $retained,
                    'timestamp' => now()->toIso8601String(),
                ]
            );

            return null;
        }

        return $result;
    }

    /**
     * Membaca status kesehatan HMI.
     */
    public function bacaStatus(string $deviceKey): array
    {
        $data = Cache::get($this->kunci($deviceKey), []);
        $diag = $data['diag'] ?? [];

        $lastSeen = $data['last_seen'] ?? null;

        $online = ($data['status'] ?? '0') === '1'
            && $lastSeen !== null
            && (time() - $lastSeen) <= self::BATAS_OFFLINE_DETIK;

        $kode = $diag['http_code'] ?? null;

        return [
            'online' => (bool) $online,
            'last_seen' => $lastSeen,
            'firmware' => $diag['fw'] ?? '-',
            'free_heap' => $diag['heap'] ?? null,
            'uptime' => isset($diag['up_min'])
                ? $diag['up_min'] * 60
                : null,
            'ip' => $data['ip'] ?? ($diag['ip'] ?? '-'),
            'ota' => $data['ota'] ?? null,

            'lan_ok' => (bool) $online,
            'mqtt_ok' => (bool) $online,

            'http_ok' => $online
                && $kode !== null
                && $kode >= 200
                && $kode < 300,

            'http_code' => $kode,
            'http_streak' => $diag['http_streak'] ?? 0,
            'http_ok_ago' => $diag['http_ok_ago_s'] ?? null,
            'lan_lost' => $diag['lan_lost'] ?? 0,
            'mqtt_lost' => $diag['mqtt_lost'] ?? 0,
            'last_outage_s' => $diag['last_outage_s'] ?? 0,
            'last_outage_cause' => $diag['last_outage_cause'] ?? 'NONE',
            'last_err' => $diag['last_err_name'] ?? '-',
            'reset' => $diag['reset'] ?? '-',
            'sensor' => $diag['sensor'] ?? '-',
        ];
    }

    /**
     * Mengirim perintah pemeriksaan OTA ke HMI.
     */
    public function kirimPerintahOta(
        string $deviceKey,
        string $versi
    ): void {
        $topik = self::TOPIC_PERINTAH[$deviceKey]
            ?? throw new RuntimeException(
                "Topik perintah untuk {$deviceKey} belum didaftarkan."
            );

        try {
            MQTT::publish($topik, 'CEK_OTA', false);
        } catch (Throwable $e) {
            $this->logger->reportException(
                $e,
                'hmi_ota_publish_error',
                [
                    'device_key' => $deviceKey,
                    'topic' => $topik,
                    'requested_version' => $versi,
                    'timestamp' => now()->toIso8601String(),
                ]
            );

            throw $e;
        }
    }
}
