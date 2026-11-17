<?php

namespace App\Support\Ota;

use Illuminate\Support\Facades\Cache;

/**
 * Penyimpanan lewat Cache Laravel. Subscriber MQTT dan web berjalan di proses berbeda,
 * jadi driver cache harus yang dipakai bersama (file, database, atau redis), bukan 'array'.
 */
final class CacheFirmwareVersionStore implements FirmwareVersionStore
{
    public function simpan(string $slug, FirmwareVersion $versi): void
    {
        Cache::forever($this->kunci($slug), ['version' => $versi->version, 'build' => $versi->build]);
    }

    public function ambil(string $slug): ?FirmwareVersion
    {
        $data = Cache::get($this->kunci($slug));
        if (! is_array($data) || ! is_string($data['version'] ?? null) || ! is_int($data['build'] ?? null)) {
            return null;
        }

        return new FirmwareVersion($data['version'], $data['build']);
    }

    private function kunci(string $slug): string
    {
        return 'ota:versi:' . $slug;
    }
}
