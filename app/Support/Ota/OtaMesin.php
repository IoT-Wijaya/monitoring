<?php

namespace App\Support\Ota;

/** Satu mesin yang bisa di-update lewat OTA: jenis perangkat dan lokasi manifest rilisnya. */
final class OtaMesin
{
    public function __construct(
        public readonly string $slug,
        public readonly string $deviceType,
        public readonly string $manifestPath,
    ) {}
}
