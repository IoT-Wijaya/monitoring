<?php

namespace App\Support\Ota;

use JsonException;

/**
 * Membaca manifest.signed: [64 byte tanda tangan Ed25519][JSON manifest].
 *
 * Server memverifikasi tanda tangan agar rilis yang rusak atau tidak sah tidak pernah
 * ditawarkan di website. Perangkat tetap memverifikasi sendiri; ini bukan pengganti.
 * Aturan kolom sama dengan tools/make_manifest.py dan ManifestParser di firmware.
 * Butuh ekstensi sodium (bawaan PHP).
 */
final class SignedManifestReader
{
    public const SIGNATURE_BYTES = 64;

    public const PUBLIC_KEY_BYTES = 32;

    public const MAX_MANIFEST_BYTES = 1024;

    private const MAX_BUILD = 4294967295;

    private const MAX_FIRMWARE_BYTES = 16 * 1024 * 1024;

    /** @throws InvalidManifestException */
    public static function read(string $signed, string $publicKey): OtaRelease
    {
        if (strlen($publicKey) !== self::PUBLIC_KEY_BYTES || $publicKey === str_repeat("\0", self::PUBLIC_KEY_BYTES)) {
            throw new InvalidManifestException('kunci publik tidak valid');
        }
        $panjang = strlen($signed);
        if ($panjang <= self::SIGNATURE_BYTES) {
            throw new InvalidManifestException('manifest terlalu pendek');
        }
        if ($panjang > self::SIGNATURE_BYTES + self::MAX_MANIFEST_BYTES) {
            throw new InvalidManifestException('manifest terlalu besar');
        }

        $tandaTangan = substr($signed, 0, self::SIGNATURE_BYTES);
        $json = substr($signed, self::SIGNATURE_BYTES);

        // Urutan penting: tanda tangan diperiksa SEBELUM isinya diurai.
        if (! sodium_crypto_sign_verify_detached($tandaTangan, $json, $publicKey)) {
            throw new InvalidManifestException('tanda tangan tidak sah');
        }

        return self::urai($json);
    }

    private static function urai(string $json): OtaRelease
    {
        try {
            $d = json_decode($json, true, 4, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidManifestException('manifest bukan JSON yang sah');
        }
        if (! is_array($d) || array_is_list($d)) {
            throw new InvalidManifestException('manifest harus berupa objek');
        }
        if (($d['schema'] ?? null) !== 1) {
            throw new InvalidManifestException('schema tidak dikenal');
        }

        $deviceType = self::teks($d, 'device_type', '/^[a-z0-9-]{1,23}$/D');
        $board = self::teks($d, 'board', '/^[a-z0-9-]{1,23}$/D');
        $version = self::teks($d, 'version', '/^[A-Za-z0-9._+-]{1,63}$/D');
        $sha256 = self::teks($d, 'sha256', '/^[0-9a-f]{64}$/D');

        $build = self::angka($d, 'build', 1, self::MAX_BUILD);
        $size = self::angka($d, 'size', 1, self::MAX_FIRMWARE_BYTES);
        $minFrom = array_key_exists('min_from_build', $d)
            ? self::angka($d, 'min_from_build', 0, self::MAX_BUILD)
            : 0;
        if ($minFrom >= $build) {
            throw new InvalidManifestException('min_from_build harus lebih kecil dari build');
        }

        return new OtaRelease($deviceType, $board, $version, $build, $size, $sha256, $minFrom);
    }

    private static function teks(array $d, string $kunci, string $pola): string
    {
        $nilai = $d[$kunci] ?? null;
        if (! is_string($nilai) || preg_match($pola, $nilai) !== 1) {
            throw new InvalidManifestException("{$kunci} tidak valid");
        }

        return $nilai;
    }

    private static function angka(array $d, string $kunci, int $min, int $max): int
    {
        $nilai = $d[$kunci] ?? null;
        if (! is_int($nilai) || $nilai < $min || $nilai > $max) {
            throw new InvalidManifestException("{$kunci} harus bilangan bulat {$min} sampai {$max}");
        }

        return $nilai;
    }
}
