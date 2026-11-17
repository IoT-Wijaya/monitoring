<?php

namespace App\Support\Ota;

use JsonException;

/**
 * Membaca muatan versi yang diterbitkan firmware (retained), misalnya:
 *   {"version":"fw-v0.1.0","build":61}
 *
 * ASUMSI: nama kunci di bawah harus sama dengan keluaran firmwareInfo.toJson() di firmware.
 * Bila berbeda, cukup ubah dua konstanta ini; tes ikut menyesuaikan lewat konstanta yang sama.
 * Kunci lain di muatan diabaikan.
 *
 * Tanpa ketergantungan pada Laravel. Muatan datang dari jaringan, jadi dianggap tidak tepercaya.
 */
final class FirmwareVersionParser
{
    public const KUNCI_VERSI = 'version';
    public const KUNCI_BUILD = 'build';

    public const MAX_BYTES = 512;

    private const MAX_BUILD = 4294967295; // uint32, sama dengan firmware
    private const MAX_PANJANG_VERSI = 64;

    /** @throws InvalidFirmwareVersionException */
    public static function parse(string $payload): FirmwareVersion
    {
        if (strlen($payload) > self::MAX_BYTES) {
            throw new InvalidFirmwareVersionException('muatan terlalu besar');
        }

        try {
            $data = json_decode($payload, true, 4, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidFirmwareVersionException('bukan JSON yang sah: ' . $e->getMessage());
        }

        if (! is_array($data) || array_is_list($data)) {
            throw new InvalidFirmwareVersionException('JSON harus berupa objek');
        }

        $versi = $data[self::KUNCI_VERSI] ?? null;
        if (! is_string($versi) || $versi === '' || strlen($versi) > self::MAX_PANJANG_VERSI) {
            throw new InvalidFirmwareVersionException(self::KUNCI_VERSI . ' harus teks 1 sampai ' . self::MAX_PANJANG_VERSI . ' karakter');
        }
        if (preg_match('/^[A-Za-z0-9._+-]+$/', $versi) !== 1) {
            throw new InvalidFirmwareVersionException(self::KUNCI_VERSI . ' memuat karakter yang tidak diizinkan');
        }

        $build = $data[self::KUNCI_BUILD] ?? null;
        if (! is_int($build) || $build < 0 || $build > self::MAX_BUILD) {
            throw new InvalidFirmwareVersionException(self::KUNCI_BUILD . ' harus bilangan bulat 0 sampai ' . self::MAX_BUILD);
        }

        return new FirmwareVersion($versi, $build);
    }
}
