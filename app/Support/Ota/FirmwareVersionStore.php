<?php

namespace App\Support\Ota;

/** Tempat menyimpan versi firmware terakhir yang dilaporkan tiap mesin. */
interface FirmwareVersionStore
{
    public function simpan(string $slug, FirmwareVersion $versi): void;

    public function ambil(string $slug): ?FirmwareVersion;
}
