<?php

namespace App\Services;

use App\Models\FirmwareRelease;

class FirmwareReleaseService
{
    /**
     * Versi terbaru yang BENAR-BENAR bisa diunduh HMI.
     * Aturannya sama persis dengan DeviceFirmwareController::latest():
     * rilis aktif, untuk device_type yang sama, dengan download_url
     * dan checksum sudah terisi. Null bila belum ada.
     */
    public function versiTerbaru(string $deviceType): ?string
    {
        return FirmwareRelease::active()
            ->where('device_type', $deviceType)
            ->whereNotNull('download_url')
            ->whereNotNull('checksum_sha256')
            ->latest('released_at')
            ->value('version');
    }
}
