<?php

namespace App\Support\Ota;

/**
 * Menyusun perintah OTA untuk satu mesin dan menolak yang pasti sia-sia atau salah sasaran.
 * Tanpa ketergantungan pada Laravel atau MQTT: hanya memutuskan, tidak mengirim.
 *
 * Aturan sama dengan yang diterapkan perangkat (UpdatePolicy dan penjagaan di
 * prosesPermintaanOta), supaya operator tidak menekan tombol yang pasti ditolak perangkat.
 */
final class OtaCommandBuilder
{
    /** @throws OtaCommandRefused */
    public static function build(
        string $slug,
        string $deviceType,
        int $runningBuild,
        OtaRelease $release,
    ): OtaCommand {
        if (preg_match('/^[a-z0-9_-]{1,40}$/D', $slug) !== 1) {
            throw new OtaCommandRefused('slug mesin tidak valid');
        }
        if ($release->deviceType !== $deviceType) {
            throw new OtaCommandRefused(
                "rilis ini untuk {$release->deviceType}, bukan {$deviceType}"
            );
        }
        if ($runningBuild < 1) {
            throw new OtaCommandRefused('build firmware yang sedang berjalan belum diketahui');
        }
        if ($release->build <= $runningBuild) {
            throw new OtaCommandRefused(
                "build {$release->build} tidak lebih baru dari build yang berjalan ({$runningBuild})"
            );
        }
        if ($runningBuild < $release->minFromBuild) {
            throw new OtaCommandRefused(
                "rilis ini hanya bisa dipasang dari build {$release->minFromBuild} ke atas; pasang rilis perantara dulu"
            );
        }

        return new OtaCommand("pabrik/{$slug}/ota_perintah", "ota {$release->build}");
    }
}
