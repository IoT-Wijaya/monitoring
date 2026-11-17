<?php

namespace App\Services;

use App\Support\Ota\FirmwareVersion;
use App\Support\Ota\FirmwareVersionParser;
use App\Support\Ota\FirmwareVersionStore;
use App\Support\Ota\InvalidFirmwareVersionException;

/**
 * Menyimpan versi firmware yang dilaporkan perangkat lewat topik versi (retained).
 * Idempoten: pesan yang sama datang berkali-kali hanya menimpa nilai yang sama.
 */
class OtaVersionService
{
    public function __construct(private FirmwareVersionStore $store) {}

    /** true bila muatan sah dan tersimpan; false bila ditolak (versi lama tetap dipertahankan). */
    public function catat(string $slug, string $payload): bool
    {
        try {
            $versi = FirmwareVersionParser::parse($payload);
        } catch (InvalidFirmwareVersionException) {
            return false;
        }

        $this->store->simpan($slug, $versi);

        return true;
    }

    public function ambil(string $slug): ?FirmwareVersion
    {
        return $this->store->ambil($slug);
    }
}
