<?php

namespace App\Services;

use App\Models\Machine;
use App\Support\Ota\InvalidManifestException;
use App\Support\Ota\OtaCommand;
use App\Support\Ota\OtaCommandBuilder;
use App\Support\Ota\OtaCommandRefused;
use App\Support\Ota\OtaMesin;
use App\Support\Ota\OtaMesinRegistry;
use App\Support\Ota\OtaRelease;
use App\Support\Ota\SignedManifestReader;
use PhpMqtt\Client\Facades\MQTT;

/**
 * Menawarkan rilis terbaru dan memerintahkan satu mesin memasangnya.
 * Semua keputusan ada di OtaCommandBuilder dan SignedManifestReader (diuji); kelas ini
 * hanya membaca berkas dan menerbitkan pesan.
 */
class OtaCommandService
{
    /**
     * Mesin untuk slug ini, atau null bila belum didaftarkan untuk OTA (config/ota.php).
     * Sebab dicatat lewat $alasan agar bisa ditampilkan kepada operator.
     */
    public function mesin(string $slug, ?string &$alasan = null): ?OtaMesin
    {
        try {
            $mesin = OtaMesinRegistry::dariConfig()->cari($slug);
        } catch (\UnexpectedValueException $e) {
            $alasan = 'konfigurasi OTA tidak valid: ' . $e->getMessage();

            return null;
        }
        if ($mesin === null) {
            $alasan = "mesin '{$slug}' belum didaftarkan untuk update OTA";
        }

        return $mesin;
    }

    /**
     * Rilis terbaru untuk mesin ini, atau null bila belum ada atau manifest tidak sah.
     * Sebab penolakan dicatat lewat $alasan agar bisa ditampilkan kepada operator.
     */
    public function rilisTerbaru(string $slug, ?string &$alasan = null): ?OtaRelease
    {
        $mesin = $this->mesin($slug, $alasan);
        if ($mesin === null) {
            return null;
        }

        $path = $mesin->manifestPath;
        $kunciHex = (string) config('ota.public_key_hex');

        if ($kunciHex === '' || ! ctype_xdigit($kunciHex) || strlen($kunciHex) !== 64) {
            $alasan = 'OTA_PUBLIC_KEY_HEX belum diisi atau bukan 64 karakter heksadesimal';

            return null;
        }
        $isi = is_file($path) ? file_get_contents($path) : false;
        if ($isi === false) {
            $alasan = 'belum ada rilis (manifest.signed tidak ditemukan)';

            return null;
        }

        try {
            return SignedManifestReader::read($isi, hex2bin($kunciHex));
        } catch (InvalidManifestException $e) {
            $alasan = 'manifest ditolak: ' . $e->getMessage();

            return null;
        }
    }

    /**
     * Menyusun perintah tanpa mengirimnya (untuk menampilkan/menguji lebih dulu).
     *
     * @throws OtaCommandRefused
     */
    public function susun(Machine $machine, int $runningBuild): OtaCommand
    {
        $slug = (string) $machine->slug;
        $rilis = $this->rilisTerbaru($slug, $alasan);
        if ($rilis === null) {
            throw new OtaCommandRefused((string) $alasan);
        }

        return OtaCommandBuilder::build(
            $slug,
            $this->mesin($slug)->deviceType, // aman: rilisTerbaru sudah memastikan mesin terdaftar
            $runningBuild,
            $rilis
        );
    }

    /**
     * Menerbitkan perintah OTA (tanpa retain: perintah lama tidak boleh terlaksana ulang).
     *
     * @throws OtaCommandRefused
     */
    public function kirim(Machine $machine, int $runningBuild): OtaCommand
    {
        $perintah = $this->susun($machine, $runningBuild);

        MQTT::publish($perintah->topic, $perintah->payload, false, 'publisher');

        return $perintah;
    }
}
