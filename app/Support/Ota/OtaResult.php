<?php

namespace App\Support\Ota;

/**
 * Hasil sebuah putaran OTA, persis kode yang dikirim firmware
 * (otaResultCode di ota_state_machine.cpp). Nilai di luar daftar ini ditolak.
 */
enum OtaResult: string
{
    case Confirmed = 'confirmed';
    case RolledBackTimeout = 'rolled_back_timeout';
    case RolledBackCrashLoop = 'rolled_back_crash_loop';
    case RolledBackNotBooted = 'rolled_back_not_booted';
    case RollbackFailed = 'rollback_failed';
    case DownloadFailed = 'download_failed';
    case VerifyFailed = 'verify_failed';
    case WriteFailed = 'write_failed';
    case Abandoned = 'abandoned';

    /** 'sukses', 'dikembalikan' (firmware lama kembali berjalan), atau 'gagal'. */
    public function kategori(): string
    {
        return match ($this) {
            self::Confirmed => 'sukses',
            self::RolledBackTimeout,
            self::RolledBackCrashLoop,
            self::RolledBackNotBooted => 'dikembalikan',
            self::RollbackFailed,
            self::DownloadFailed,
            self::VerifyFailed,
            self::WriteFailed,
            self::Abandoned => 'gagal',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Berhasil, firmware baru berjalan sehat',
            self::RolledBackTimeout => 'Dikembalikan ke versi lama: firmware baru tidak sehat dalam batas waktu',
            self::RolledBackCrashLoop => 'Dikembalikan ke versi lama: firmware baru terus restart',
            self::RolledBackNotBooted => 'Dikembalikan ke versi lama: firmware baru tidak pernah menyala',
            self::RollbackFailed => 'Gagal mengembalikan ke versi lama, perlu pemeriksaan langsung',
            self::DownloadFailed => 'Gagal mengunduh firmware',
            self::VerifyFailed => 'Firmware ditolak pemeriksaan (tanda tangan, hash, atau kebijakan)',
            self::WriteFailed => 'Gagal menulis firmware ke flash',
            self::Abandoned => 'Dibatalkan setelah beberapa percobaan gagal',
        };
    }
}
