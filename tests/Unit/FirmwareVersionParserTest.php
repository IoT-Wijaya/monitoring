<?php

namespace Tests\Unit;

use App\Support\Ota\FirmwareVersionParser as P;
use App\Support\Ota\InvalidFirmwareVersionException;
use PHPUnit\Framework\TestCase;

class FirmwareVersionParserTest extends TestCase
{
    private function json(array $d): string
    {
        return json_encode($d, JSON_THROW_ON_ERROR);
    }

    private function harusDitolak(string $payload): void
    {
        try {
            P::parse($payload);
        } catch (InvalidFirmwareVersionException) {
            $this->addToAssertionCount(1);

            return;
        }
        $this->fail('seharusnya ditolak: ' . $payload);
    }

    public function test_muatan_sah_dibaca(): void
    {
        $v = P::parse($this->json([P::KUNCI_VERSI => 'fw-v0.1.0', P::KUNCI_BUILD => 61]));

        $this->assertSame('fw-v0.1.0', $v->version);
        $this->assertSame(61, $v->build);
    }

    public function test_kunci_tambahan_dan_urutan_tidak_masalah(): void
    {
        $v = P::parse('{"board":"nodemcu-32s","build":7,"version":"fw-v1.2.3","uptime":99}');

        $this->assertSame('fw-v1.2.3', $v->version);
        $this->assertSame(7, $v->build);
    }

    public function test_build_nol_dan_uint32_terbesar_diterima(): void
    {
        $this->assertSame(0, P::parse($this->json([P::KUNCI_VERSI => 'a', P::KUNCI_BUILD => 0]))->build);
        $this->assertSame(4294967295, P::parse($this->json([P::KUNCI_VERSI => 'a', P::KUNCI_BUILD => 4294967295]))->build);
    }

    public function test_build_di_luar_jangkauan_atau_salah_tipe_ditolak(): void
    {
        foreach ([-1, 4294967296, '61', 61.5, null, true, [61]] as $salah) {
            $this->harusDitolak($this->json([P::KUNCI_VERSI => 'fw-v0.1.0', P::KUNCI_BUILD => $salah]));
        }
    }

    public function test_build_hilang_ditolak(): void
    {
        $this->harusDitolak($this->json([P::KUNCI_VERSI => 'fw-v0.1.0']));
    }

    public function test_versi_hilang_kosong_atau_salah_tipe_ditolak(): void
    {
        $this->harusDitolak($this->json([P::KUNCI_BUILD => 5]));
        foreach (['', 5, null, ['x'], false] as $salah) {
            $this->harusDitolak($this->json([P::KUNCI_VERSI => $salah, P::KUNCI_BUILD => 5]));
        }
    }

    public function test_versi_dengan_karakter_berbahaya_ditolak(): void
    {
        foreach (['<script>', 'fw v1', "fw\nv1", 'fw/../v1', 'fw;v1', "fw-v1'"] as $salah) {
            $this->harusDitolak($this->json([P::KUNCI_VERSI => $salah, P::KUNCI_BUILD => 5]));
        }
    }

    public function test_versi_terlalu_panjang_ditolak_dan_batas_diterima(): void
    {
        $this->harusDitolak($this->json([P::KUNCI_VERSI => str_repeat('a', 65), P::KUNCI_BUILD => 5]));
        $this->assertSame(64, strlen(P::parse($this->json([P::KUNCI_VERSI => str_repeat('a', 64), P::KUNCI_BUILD => 5]))->version));
    }

    public function test_bukan_objek_json_ditolak(): void
    {
        foreach (['', 'abc', '[]', '[1,2]', '5', '"fw"', 'null', '{"version":'] as $salah) {
            $this->harusDitolak($salah);
        }
    }

    public function test_muatan_terlalu_besar_ditolak(): void
    {
        $this->harusDitolak($this->json([P::KUNCI_VERSI => 'a', P::KUNCI_BUILD => 1, 'x' => str_repeat('y', P::MAX_BYTES)]));
    }
}
