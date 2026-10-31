<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use PhpMqtt\Client\Facades\MQTT;

class DeviceHealthService
{
    // HMI mengirim heartbeat tiap 5 detik, jadi 15 detik sudah cukup.
    private const BATAS_OFFLINE_DETIK = 15;

    private const TOPIC_PERINTAH = [
        'sanding-hmi' => 'pabrik/hmi_sanding/perintah',
    ];

    private function kunci(string $deviceKey): string
    {
        return "device:{$deviceKey}:status";
    }

    // Dipanggil listener MQTT setiap ada pesan dari topik HMI.
    // $subTopik: "status", "ip", atau "diag". $retained: true jika pesan lama dari broker.
    public function simpanPesan(string $deviceKey, string $subTopik, string $payload, bool $retained = false): void
    {
        $data = Cache::get($this->kunci($deviceKey), []);

        if ($subTopik === 'status') {
            $data['status'] = trim($payload); // "1" atau "0" (Last Will)
            // Pesan retained bukan bukti HMI hidup sekarang, jadi jangan menyegarkan last_seen.
            if (! $retained) {
                $data['last_seen'] = time();
            }
        } elseif ($subTopik === 'ip') {
            $data['ip'] = trim($payload);
        } elseif ($subTopik === 'diag') {
            $diag = json_decode($payload, true);
            if (is_array($diag)) {
                $data['diag'] = $diag;
            }
        }

        Cache::forever($this->kunci($deviceKey), $data);
    }

    public function bacaStatus(string $deviceKey): array
    {
        $data = Cache::get($this->kunci($deviceKey), []);
        $diag = $data['diag'] ?? [];

        $lastSeen = $data['last_seen'] ?? null;
        $online = ($data['status'] ?? '0') === '1'
            && $lastSeen
            && (time() - $lastSeen) <= self::BATAS_OFFLINE_DETIK;

        $kode = $diag['http_code'] ?? null;

        return [
            'online'            => (bool) $online,
            'last_seen'         => $lastSeen,
            'firmware'          => $diag['fw'] ?? '-',
            'free_heap'         => $diag['heap'] ?? null,
            'uptime'            => isset($diag['up_min']) ? $diag['up_min'] * 60 : null,
            'ip'                => $data['ip'] ?? ($diag['ip'] ?? '-'),
            // HMI hanya bisa mengirim pesan jika LAN dan MQTT sama-sama hidup
            'lan_ok'            => (bool) $online,
            'mqtt_ok'           => (bool) $online,
            'http_ok'           => $online && $kode !== null && $kode >= 200 && $kode < 300,
            'http_code'         => $kode,
            'http_streak'       => $diag['http_streak'] ?? 0,
            'http_ok_ago'       => $diag['http_ok_ago_s'] ?? null,
            'lan_lost'          => $diag['lan_lost'] ?? 0,
            'mqtt_lost'         => $diag['mqtt_lost'] ?? 0,
            'last_outage_s'     => $diag['last_outage_s'] ?? 0,
            'last_outage_cause' => $diag['last_outage_cause'] ?? 'NONE',
            'last_err'          => $diag['last_err_name'] ?? '-',
            'reset'             => $diag['reset'] ?? '-',
            'sensor'            => $diag['sensor'] ?? '-',
        ];
    }

    public function kirimPerintahOta(string $deviceKey, string $versi): void
    {
        $topik = self::TOPIC_PERINTAH[$deviceKey]
            ?? throw new \RuntimeException("Topik perintah untuk {$deviceKey} belum didaftarkan.");

        // retain = false: perintah ini tidak boleh dikirim ulang broker tiap HMI konek.
        MQTT::publish($topik, 'CEK_OTA', false);
    }
}
