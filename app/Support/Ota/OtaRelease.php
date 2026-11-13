<?php

namespace App\Support\Ota;

/** Satu rilis firmware, dibaca dari manifest yang sudah lolos verifikasi tanda tangan. */
final class OtaRelease
{
    public function __construct(
        public readonly string $deviceType,
        public readonly string $board,
        public readonly string $version,
        public readonly int $build,
        public readonly int $size,
        public readonly string $sha256,
        public readonly int $minFromBuild,
    ) {}
}
