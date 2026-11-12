<?php

namespace App\Support\Ota;

/** Satu laporan hasil OTA dari perangkat. Tidak berubah setelah dibuat. */
final class OtaStatus
{
    public function __construct(
        public readonly OtaResult $result,
        public readonly int $targetBuild,
        public readonly int $previousBuild,
        public readonly int $runningBuild,
    ) {}
}
