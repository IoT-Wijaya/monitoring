<?php

namespace App\Support\Ota;

/**
 * Posisi firmware sebuah mesin dibanding rilis terbaru. Murni perbandingan nomor build,
 * tanpa ketergantungan pada Laravel.
 */
enum FirmwareStatus: string
{
    case Terbaru = 'terbaru';
    case TersediaUpdate = 'tersedia_update';
    case LebihBaruDariRilis = 'lebih_baru_dari_rilis';
    case TanpaRilis = 'tanpa_rilis';
    case TidakDiketahui = 'tidak_diketahui';
    case TanpaNomorBuild = 'tanpa_nomor_build';

    /**
     * @param  int|null  $berjalan  build di mesin, null bila belum pernah melapor
     * @param  int|null  $tersedia  build rilis terbaru, null bila belum ada rilis sah
     */
    public static function tentukan(?int $berjalan, ?int $tersedia): self
    {
        if ($berjalan === null) {
            return self::TidakDiketahui;
        }
        // Build 0 = firmware lokal (bukan hasil CI): tidak bisa dibandingkan dan ditolak
        // OtaCommandBuilder, jadi jangan ditawari update.
        if ($berjalan < 1) {
            return self::TanpaNomorBuild;
        }
        if ($tersedia === null) {
            return self::TanpaRilis;
        }

        return match (true) {
            $tersedia > $berjalan => self::TersediaUpdate,
            $tersedia === $berjalan => self::Terbaru,
            default => self::LebihBaruDariRilis,
        };
    }

    public function bisaUpdate(): bool
    {
        return $this === self::TersediaUpdate;
    }

    public function label(): string
    {
        return match ($this) {
            self::Terbaru => 'Sudah versi terbaru',
            self::TersediaUpdate => 'Ada versi baru',
            self::LebihBaruDariRilis => 'Lebih baru dari rilis resmi',
            self::TanpaRilis => 'Belum ada rilis resmi',
            self::TidakDiketahui => 'Versi mesin belum diketahui',
            self::TanpaNomorBuild => 'Firmware lokal tanpa nomor build; pasang rilis resmi lewat kabel dulu',
        };
    }
}
