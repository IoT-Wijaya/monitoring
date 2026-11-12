<?php

namespace App\Support\Ota;

use JsonException;

/**
 * Membaca muatan topik ota_status, misalnya:
 *   {"result":"confirmed","target":5,"previous":4,"build":5}
 *
 * Tanpa ketergantungan pada Laravel. Muatan datang dari jaringan, jadi dianggap tidak
 * tepercaya: ukuran dibatasi, tipe diperiksa ketat, dan nilai di luar daftar ditolak.
 */
final class OtaStatusParser
{
    public const MAX_BYTES = 512;

    private const MAX_BUILD = 4294967295; // uint32, sama dengan firmware

    /** @throws InvalidOtaStatusException */
    public static function parse(string $payload): OtaStatus
    {
        if (strlen($payload) > self::MAX_BYTES) {
            throw new InvalidOtaStatusException('muatan terlalu besar');
        }

        try {
            $data = json_decode($payload, true, 4, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidOtaStatusException('bukan JSON yang sah: ' . $e->getMessage());
        }

        if (! is_array($data) || array_is_list($data)) {
            throw new InvalidOtaStatusException('JSON harus berupa objek');
        }

        $result = $data['result'] ?? null;
        if (! is_string($result) || ($hasil = OtaResult::tryFrom($result)) === null) {
            throw new InvalidOtaStatusException('result tidak dikenal');
        }

        return new OtaStatus(
            $hasil,
            self::bacaBuild($data, 'target'),
            self::bacaBuild($data, 'previous'),
            self::bacaBuild($data, 'build'),
        );
    }

    private static function bacaBuild(array $data, string $kunci): int
    {
        $nilai = $data[$kunci] ?? null;
        if (! is_int($nilai) || $nilai < 0 || $nilai > self::MAX_BUILD) {
            throw new InvalidOtaStatusException("{$kunci} harus bilangan bulat 0 sampai " . self::MAX_BUILD);
        }

        return $nilai;
    }
}
