<?php

namespace App\Support\Ota;

/**
 * Daftar mesin yang boleh di-update lewat OTA, dibaca dari config/ota.php.
 * Tanpa ketergantungan pada Laravel (kecuali dariConfig): hanya memutuskan dan memeriksa.
 */
final class OtaMesinRegistry
{
    private const NAMA_AMAN = '/^[a-z0-9-]{1,23}$/D';

    /**
     * @param  array<string, array{device_type?: mixed, folder?: mixed}>  $mesin  dikunci dengan slug
     */
    public function __construct(
        private readonly array $mesin,
        private readonly string $root,
    ) {}

    public static function dariConfig(): self
    {
        return new self((array) config('ota.mesin', []), (string) config('ota.manifest_root'));
    }

    /**
     * Mesin untuk slug ini, atau null bila belum didaftarkan untuk OTA.
     *
     * @throws \UnexpectedValueException bila konfigurasinya sendiri salah (kesalahan pemrogram,
     *                                   bukan kesalahan operator); pesan siap ditampilkan.
     */
    public function cari(string $slug): ?OtaMesin
    {
        if (! isset($this->mesin[$slug]) || ! is_array($this->mesin[$slug])) {
            return null;
        }
        if (trim($this->root) === '') {
            throw new \UnexpectedValueException('ota.manifest_root belum diisi');
        }

        $deviceType = $this->mesin[$slug]['device_type'] ?? null;
        $folder = $this->mesin[$slug]['folder'] ?? null;
        foreach (['device_type' => $deviceType, 'folder' => $folder] as $nama => $nilai) {
            if (! is_string($nilai) || preg_match(self::NAMA_AMAN, $nilai) !== 1) {
                throw new \UnexpectedValueException(
                    "ota.mesin.{$slug}.{$nama} harus a-z, 0-9, '-' dan 1-23 karakter"
                );
            }
        }

        return new OtaMesin(
            $slug,
            $deviceType,
            rtrim($this->root, '/') . '/' . $folder . '/manifest.signed',
        );
    }
}
