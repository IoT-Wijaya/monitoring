<?php

namespace App\Support\Ota;

/**
 * Tahap sebuah putaran update OTA, dibaca dari tanda-tanda yang terlihat di server.
 * Murni data dan teks, tanpa ketergantungan pada Laravel.
 */
enum OtaFase: string
{
    case Terkirim = 'terkirim';
    case Memasang = 'memasang';
    case FirmwareBaruBerjalan = 'firmware_baru_berjalan';
    case Berhasil = 'berhasil';
    case Dikembalikan = 'dikembalikan';
    case Gagal = 'gagal';
    case TanpaKabar = 'tanpa_kabar';

    /** Urutan langkah yang ditampilkan sebagai stepper. */
    public const LANGKAH = [
        'Perintah terkirim',
        'Mesin memasang',
        'Firmware baru berjalan',
        'Selesai',
    ];

    /** Indeks langkah yang sedang berjalan (0 sampai 3) pada LANGKAH. */
    public function langkahAktif(): int
    {
        return match ($this) {
            self::Terkirim => 0,
            self::Memasang, self::TanpaKabar => 1,
            self::FirmwareBaruBerjalan => 2,
            self::Berhasil, self::Dikembalikan, self::Gagal => 3,
        };
    }

    /** Perkiraan kemajuan 0 sampai 100. Ini perkiraan per tahap, bukan persentase unduhan. */
    public function persen(): int
    {
        return match ($this) {
            self::Terkirim => 20,
            self::Memasang, self::TanpaKabar => 55,
            self::FirmwareBaruBerjalan => 85,
            self::Berhasil, self::Dikembalikan, self::Gagal => 100,
        };
    }

    public function selesai(): bool
    {
        return match ($this) {
            self::Berhasil, self::Dikembalikan, self::Gagal, self::TanpaKabar => true,
            default => false,
        };
    }

    /** 'berjalan', 'sukses', 'peringatan', atau 'galat': dipakai untuk memilih warna. */
    public function nada(): string
    {
        return match ($this) {
            self::Terkirim, self::Memasang, self::FirmwareBaruBerjalan => 'berjalan',
            self::Berhasil => 'sukses',
            self::Dikembalikan, self::TanpaKabar => 'peringatan',
            self::Gagal => 'galat',
        };
    }

    public function judul(): string
    {
        return match ($this) {
            self::Terkirim => 'Perintah update terkirim',
            self::Memasang => 'Mesin sedang memasang firmware',
            self::FirmwareBaruBerjalan => 'Firmware baru berjalan',
            self::Berhasil => 'Update berhasil',
            self::Dikembalikan => 'Update dibatalkan, versi lama dipulihkan',
            self::Gagal => 'Update gagal',
            self::TanpaKabar => 'Tidak ada kabar dari mesin',
        };
    }

    public function keterangan(): string
    {
        return match ($this) {
            self::Terkirim => 'Menunggu mesin menerima perintah dan restart.',
            self::Memasang => 'Mesin mengunduh dan menulis firmware. Jangan matikan daya.',
            self::FirmwareBaruBerjalan => 'Menunggu mesin membuktikan dirinya sehat dan melapor.',
            self::Berhasil => 'Firmware baru berjalan sehat dan sudah dikonfirmasi mesin.',
            self::Dikembalikan => 'Firmware baru tidak lolos pemeriksaan sehat, mesin kembali ke versi sebelumnya.',
            self::Gagal => 'Firmware tidak terpasang. Mesin tetap memakai versi sebelumnya.',
            self::TanpaKabar => 'Batas waktu habis tanpa laporan hasil. Periksa mesin secara langsung.',
        };
    }
}
