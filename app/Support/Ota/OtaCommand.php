<?php

namespace App\Support\Ota;

/** Pesan MQTT yang akan diterbitkan untuk memerintahkan satu mesin memasang update. */
final class OtaCommand
{
    public function __construct(
        public readonly string $topic,
        public readonly string $payload,
    ) {}
}
