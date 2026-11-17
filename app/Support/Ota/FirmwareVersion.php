<?php

namespace App\Support\Ota;

/** Versi firmware yang sedang berjalan di satu mesin, sesuai laporan perangkat. */
final class FirmwareVersion
{
    public function __construct(
        public readonly string $version,
        public readonly int $build,
    ) {}
}
