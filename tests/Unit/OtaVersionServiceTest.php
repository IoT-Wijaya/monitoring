<?php

namespace Tests\Unit;

use App\Services\OtaVersionService;
use App\Support\Ota\FirmwareVersion;
use App\Support\Ota\FirmwareVersionStore;
use PHPUnit\Framework\TestCase;

class MemoriVersi implements FirmwareVersionStore
{
    public array $data = [];
    public int $jumlahSimpan = 0;

    public function simpan(string $slug, FirmwareVersion $versi): void
    {
        $this->jumlahSimpan++;
        $this->data[$slug] = $versi;
    }

    public function ambil(string $slug): ?FirmwareVersion
    {
        return $this->data[$slug] ?? null;
    }
}

class OtaVersionServiceTest extends TestCase
{
    private const SAH = '{"version":"fw-v0.1.0","build":61}';

    public function test_muatan_sah_disimpan_per_mesin(): void
    {
        $m = new MemoriVersi();
        $s = new OtaVersionService($m);

        $this->assertTrue($s->catat('sanding', self::SAH));
        $this->assertSame(61, $s->ambil('sanding')->build);
        $this->assertNull($s->ambil('mesin-lain'));
    }

    public function test_muatan_rusak_ditolak_dan_nilai_lama_dipertahankan(): void
    {
        $m = new MemoriVersi();
        $s = new OtaVersionService($m);
        $s->catat('sanding', self::SAH);

        $this->assertSame(false, $s->catat('sanding', 'sampah'));
        $this->assertSame(false, $s->catat('sanding', '{"version":"x","build":-1}'));
        $this->assertSame(61, $s->ambil('sanding')->build);
        $this->assertSame(1, $m->jumlahSimpan);
    }

    public function test_pesan_sama_berulang_idempoten(): void
    {
        $m = new MemoriVersi();
        $s = new OtaVersionService($m);
        $s->catat('sanding', self::SAH);
        $s->catat('sanding', self::SAH);

        $this->assertSame('fw-v0.1.0', $s->ambil('sanding')->version);
        $this->assertSame(61, $s->ambil('sanding')->build);
    }

    public function test_versi_baru_menimpa_versi_lama(): void
    {
        $m = new MemoriVersi();
        $s = new OtaVersionService($m);
        $s->catat('sanding', self::SAH);
        $s->catat('sanding', '{"version":"fw-v0.1.1","build":62}');

        $this->assertSame(62, $s->ambil('sanding')->build);
    }
}
