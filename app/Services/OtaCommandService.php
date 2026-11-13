<?php

namespace App\Services;

use App\Models\Machine;
use App\Support\Ota\InvalidManifestException;
use App\Support\Ota\OtaCommand;
use App\Support\Ota\OtaCommandBuilder;
use App\Support\Ota\OtaCommandRefused;
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
     * Rilis terbaru yang tersedia, atau null bila belum ada atau manifest tidak sah.
     * Sebab penolakan dicatat lewat $alasan agar bisa ditampilkan kepada operator.
     */
    public function rilisTerbaru(?string &$alasan = null): ?OtaRelease
    {
        $path = (string) config('ota.manifest_path');
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
        $rilis = $this->rilisTerbaru($alasan);
        if ($rilis === null) {
            throw new OtaCommandRefused((string) $alasan);
        }

        return OtaCommandBuilder::build(
            (string) $machine->slug,
            (string) config('ota.device_type'),
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
