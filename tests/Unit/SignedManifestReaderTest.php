<?php

namespace Tests\Unit;

use App\Support\Ota\InvalidManifestException;
use App\Support\Ota\SignedManifestReader;
use PHPUnit\Framework\TestCase;

/**
 * Data uji NYATA: manifest.signed dibuat oleh tools/make_manifest.py dan
 * tools/sign_manifest.sh (openssl, Ed25519) dari firmware 300000 byte acak,
 * --version fw-v0.0.6 --build 6. Kunci rahasianya sudah dibuang; hanya kunci publik di sini.
 */
class SignedManifestReaderTest extends TestCase
{
    private const SIGNED_HEX =
    'd688b433fe36a21339ed6c25cd3565b9e4b49c71763e4ae617dd8994501b0e4f4f303eec2a5604bf08c1462746753160' .
        'a225a57167b59a4b4c824225a810e90a7b22736368656d61223a312c226465766963655f74797065223a2273616e6469' .
        '6e672d6b6563696c222c22626f617264223a226e6f64656d63752d333273222c2276657273696f6e223a2266772d7630' .
        '2e302e36222c226275696c64223a362c2273697a65223a3330303030302c22736861323536223a226365316431643165' .
        '643066626562353965313261363536633665656462663539303032306265373139396535633838323638313032613730' .
        '6638323639383835227d';

    private const PUBLIC_KEY_HEX = '32d41558de426826645c881cc349fadb28e06ac722d21ebe5900e30cb39874c1';

    private const OTHER_PUBLIC_KEY_HEX = 'dc039539cb7ea401df55c3a2bcf8dde1b002f462a0fc2f6d6ac42b63b1f45769';

    private function signed(): string
    {
        return hex2bin(self::SIGNED_HEX);
    }

    private function kunci(): string
    {
        return hex2bin(self::PUBLIC_KEY_HEX);
    }

    public function test_manifest_nyata_dibaca_lengkap(): void
    {
        $r = SignedManifestReader::read($this->signed(), $this->kunci());

        $this->assertSame('sanding-kecil', $r->deviceType);
        $this->assertSame('nodemcu-32s', $r->board);
        $this->assertSame('fw-v0.0.6', $r->version);
        $this->assertSame(6, $r->build);
        $this->assertSame(300000, $r->size);
        $this->assertSame('ce1d1d1ed0fbeb59e12a656c6eedbf590020be7199e5c88268102a70f8269885', $r->sha256);
        $this->assertSame(0, $r->minFromBuild);
    }

    public function test_kunci_publik_lain_ditolak(): void
    {
        $this->assertDitolak($this->signed(), hex2bin(self::OTHER_PUBLIC_KEY_HEX), 'tanda tangan');
    }

    public function test_satu_bit_berubah_di_json_ditolak(): void
    {
        $s = $this->signed();
        $s[100] = chr(ord($s[100]) ^ 0x01);
        $this->assertDitolak($s, $this->kunci(), 'tanda tangan');
    }

    public function test_satu_bit_berubah_di_tanda_tangan_ditolak(): void
    {
        $s = $this->signed();
        $s[10] = chr(ord($s[10]) ^ 0x01);
        $this->assertDitolak($s, $this->kunci(), 'tanda tangan');
    }

    public function test_manifest_terpotong_atau_terlalu_pendek_ditolak(): void
    {
        $this->assertDitolak('', $this->kunci(), 'pendek');
        $this->assertDitolak(str_repeat('a', 64), $this->kunci(), 'pendek');
        $this->assertDitolak(substr($this->signed(), 0, -1), $this->kunci(), 'tanda tangan');
    }

    public function test_manifest_terlalu_besar_ditolak_sebelum_verifikasi(): void
    {
        $this->assertDitolak(str_repeat('a', 64 + 1025), $this->kunci(), 'besar');
    }

    public function test_kunci_publik_salah_ukuran_atau_nol_ditolak(): void
    {
        $this->assertDitolak($this->signed(), '', 'kunci');
        $this->assertDitolak($this->signed(), substr($this->kunci(), 0, 31), 'kunci');
        $this->assertDitolak($this->signed(), str_repeat("\0", 32), 'kunci');
    }

    public function test_manifest_bertanda_tangan_sah_tetapi_isinya_salah_ditolak(): void
    {
        // Ditandatangani kunci yang benar-benar sah (dibuat sementara), tetapi isinya melanggar aturan.
        $pasangan = sodium_crypto_sign_keypair();
        $rahasia = sodium_crypto_sign_secretkey($pasangan);
        $publik = sodium_crypto_sign_publickey($pasangan);
        $dasar = [
            'schema' => 1,
            'device_type' => 'sanding-kecil',
            'board' => 'nodemcu-32s',
            'version' => 'fw-v1',
            'build' => 7,
            'size' => 1000,
            'sha256' => str_repeat('a', 64),
        ];

        $r = SignedManifestReader::read($this->tandatangani($dasar, $rahasia), $publik);
        $this->assertSame(7, $r->build);

        $buruk = [
            ['schema' => 2],
            ['device_type' => 'Sanding Kecil'],
            ['board' => ''],
            ['version' => 'a b'],
            ['version' => "fw\n"],
            ['build' => 0],
            ['build' => 4294967296],
            ['build' => '7'],
            ['size' => 0],
            ['size' => 16 * 1024 * 1024 + 1],
            ['sha256' => str_repeat('A', 64)],
            ['sha256' => str_repeat('a', 63)],
            ['min_from_build' => 7],
            ['min_from_build' => -1],
        ];
        foreach ($buruk as $ubah) {
            $this->assertDitolak($this->tandatangani(array_merge($dasar, $ubah), $rahasia), $publik, '', json_encode($ubah));
        }
        $tanpa = $dasar;
        unset($tanpa['sha256']);
        $this->assertDitolak($this->tandatangani($tanpa, $rahasia), $publik, '', 'tanpa sha256');

        $this->assertDitolak($this->tandatanganiMentah('[1,2]', $rahasia), $publik, '', 'bukan objek');
        $this->assertDitolak($this->tandatanganiMentah('bukan json', $rahasia), $publik, '', 'bukan json');

        $ok = array_merge($dasar, ['min_from_build' => 3]);
        $this->assertSame(3, SignedManifestReader::read($this->tandatangani($ok, $rahasia), $publik)->minFromBuild);
    }

    private function tandatangani(array $data, string $rahasia): string
    {
        return $this->tandatanganiMentah(json_encode($data, JSON_UNESCAPED_SLASHES), $rahasia);
    }

    private function tandatanganiMentah(string $json, string $rahasia): string
    {
        return sodium_crypto_sign_detached($json, $rahasia) . $json;
    }

    private function assertDitolak(string $signed, string $kunci, string $kataKunci, string $info = ''): void
    {
        try {
            SignedManifestReader::read($signed, $kunci);
        } catch (InvalidManifestException $e) {
            if ($kataKunci !== '') {
                $this->assertTrue(str_contains($e->getMessage(), $kataKunci), "pesan: {$e->getMessage()}");
            } else {
                $this->addToAssertionCount(1);
            }

            return;
        }
        $this->fail('seharusnya ditolak ' . $info);
    }
}
