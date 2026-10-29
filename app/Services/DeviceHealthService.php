<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class DeviceHealthService
{
    // Perangkat dianggap offline kalau tidak ada sinyal lebih dari 30 detik.
    private const BATAS_OFFLINE_DETIK = 30;

    public function bacaStatus(string $deviceKey): array
    {
        $data = Cache::get("device:{$deviceKey}:status", []);

        $lastSeen = $data['last_seen'] ?? null;
        $data['online'] = $lastSeen && (time() - $lastSeen) <= self::BATAS_OFFLINE_DETIK;

        return $data;
    }

    public function kirimPerintahOta(string $deviceKey, string $versi): void
    {
        // Publish ke topik MQTT khusus HMI, mis. "device/{$deviceKey}/cmd"
        // dengan payload {"cmd":"ota","version":"..."}.
        // Isi sesuai cara reset() di MachineCounterService mengirim perintah MQTT.
    }
}
