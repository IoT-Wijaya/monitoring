<?php

namespace Tests\Unit;

use App\Support\Ota\OtaCommandBuilder;
use App\Support\Ota\OtaCommandRefused;
use App\Support\Ota\OtaRelease;
use PHPUnit\Framework\TestCase;

class OtaCommandBuilderTest extends TestCase
{
    private function rilis(int $build = 6, string $tipe = 'sanding-kecil', int $minFrom = 0): OtaRelease
    {
        return new OtaRelease($tipe, 'nodemcu-32s', "fw-v0.0.{$build}", $build, 300000, str_repeat('a', 64), $minFrom);
    }

    public function test_rilis_lebih_baru_menghasilkan_topik_dan_muatan_yang_dikenal_firmware(): void
    {
        $c = OtaCommandBuilder::build('sanding', 'sanding-kecil', 5, $this->rilis(6));

        $this->assertSame('pabrik/sanding/ota_perintah', $c->topic);
        $this->assertSame('ota 6', $c->payload);
    }

    public function test_build_sama_atau_lebih_lama_ditolak(): void
    {
        $this->assertDitolak(fn() => OtaCommandBuilder::build('sanding', 'sanding-kecil', 6, $this->rilis(6)), 'tidak lebih baru');
        $this->assertDitolak(fn() => OtaCommandBuilder::build('sanding', 'sanding-kecil', 9, $this->rilis(6)), 'tidak lebih baru');
    }

    public function test_jenis_perangkat_berbeda_ditolak(): void
    {
        $this->assertDitolak(fn() => OtaCommandBuilder::build('sanding', 'sanding-kecil', 5, $this->rilis(6, 'hmi-sanding')), 'bukan sanding-kecil');
    }

    public function test_build_berjalan_tidak_diketahui_ditolak(): void
    {
        $this->assertDitolak(fn() => OtaCommandBuilder::build('sanding', 'sanding-kecil', 0, $this->rilis(6)), 'belum diketahui');
    }

    public function test_batas_min_from_build_dihormati(): void
    {
        $r = $this->rilis(10, 'sanding-kecil', 8);
        $this->assertDitolak(fn() => OtaCommandBuilder::build('sanding', 'sanding-kecil', 7, $r), 'perantara');
        $this->assertSame('ota 10', OtaCommandBuilder::build('sanding', 'sanding-kecil', 8, $r)->payload);
    }

    public function test_slug_tidak_aman_ditolak_agar_topik_tidak_bisa_dimanipulasi(): void
    {
        foreach (['', 'a/b', 'a#', 'a+', 'Sanding', 'a b', "a\n", '../x', str_repeat('a', 41)] as $slug) {
            $this->assertDitolak(fn() => OtaCommandBuilder::build($slug, 'sanding-kecil', 5, $this->rilis(6)), 'slug', $slug);
        }
        $this->assertSame('pabrik/sanding_kecil-2/ota_perintah', OtaCommandBuilder::build('sanding_kecil-2', 'sanding-kecil', 5, $this->rilis(6))->topic);
    }

    private function assertDitolak(callable $aksi, string $kataKunci, string $info = ''): void
    {
        try {
            $aksi();
        } catch (OtaCommandRefused $e) {
            $this->assertTrue(str_contains($e->getMessage(), $kataKunci), "pesan: {$e->getMessage()}");

            return;
        }
        $this->fail('seharusnya ditolak ' . $info);
    }
}
