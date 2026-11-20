<?php

namespace App\Support\Ota;

/**
 * Menentukan tahap update OTA dari tanda-tanda di server. Tanpa ketergantungan pada Laravel.
 *
 * Mesin tidak melaporkan kemajuan unduhan; unduhan terjadi saat boot sebelum MQTT tersambung.
 * Jadi tahap disimpulkan dari: laporan hasil (ota_status), build yang dilaporkan mesin,
 * dan apakah mesin pernah terlihat offline sejak perintah dikirim.
 */
final class OtaProgress
{
    public const BATAS_DETIK = 900;

    /**
     * @param  int  $targetBuild  build tujuan pada perintah yang dikirim
     * @param  int  $dikirimPada  waktu perintah dikirim (detik epoch)
     * @param  bool  $pernahOffline  mesin terlihat offline setelah perintah dikirim
     * @param  int|null  $buildBerjalan  build terakhir yang dilaporkan mesin
     * @param  OtaResult|null  $hasil  laporan hasil OTA terakhir yang tersimpan
     * @param  int|null  $targetLaporan  target yang disebut laporan itu
     * @param  int|null  $dilaporkanPada  waktu laporan itu diterima (detik epoch)
     */
    public static function tentukan(
        int $targetBuild,
        int $dikirimPada,
        int $sekarang,
        bool $pernahOffline,
        bool $online,
        ?int $buildBerjalan,
        ?OtaResult $hasil,
        ?int $targetLaporan,
        ?int $dilaporkanPada,
        int $batasDetik = self::BATAS_DETIK,
    ): OtaFase {
        // Laporan hanya dihitung bila untuk target yang sama dan diterima setelah perintah:
        // laporan lama yang dikirim ulang broker (retained) tidak boleh mengakhiri putaran baru.
        if (
            $hasil !== null
            && $targetLaporan === $targetBuild
            && $dilaporkanPada !== null
            && $dilaporkanPada >= $dikirimPada
        ) {
            return match ($hasil->kategori()) {
                'sukses' => OtaFase::Berhasil,
                'dikembalikan' => OtaFase::Dikembalikan,
                default => OtaFase::Gagal,
            };
        }

        if ($sekarang - $dikirimPada > $batasDetik) {
            return OtaFase::TanpaKabar;
        }

        if ($online && $buildBerjalan === $targetBuild) {
            return OtaFase::FirmwareBaruBerjalan;
        }

        return $pernahOffline ? OtaFase::Memasang : OtaFase::Terkirim;
    }
}
