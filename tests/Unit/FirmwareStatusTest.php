<?php

namespace Tests\Unit;

use App\Support\Ota\FirmwareStatus as S;
use PHPUnit\Framework\TestCase;

class FirmwareStatusTest extends TestCase
{
    public function test_rilis_lebih_tinggi_berarti_ada_update(): void
    {
        $this->assertSame(S::TersediaUpdate, S::tentukan(60, 61));
    }

    public function test_build_sama_berarti_terbaru(): void
    {
        $this->assertSame(S::Terbaru, S::tentukan(61, 61));
    }

    public function test_mesin_lebih_baru_dari_rilis_tidak_ditawari_update(): void
    {
        $s = S::tentukan(62, 61);

        $this->assertSame(S::LebihBaruDariRilis, $s);
        $this->assertSame(false, $s->bisaUpdate());
    }

    public function test_mesin_belum_melapor_tidak_diketahui_walau_rilis_ada(): void
    {
        $this->assertSame(S::TidakDiketahui, S::tentukan(null, 61));
        $this->assertSame(S::TidakDiketahui, S::tentukan(null, null));
    }

    public function test_tanpa_rilis_sah(): void
    {
        $this->assertSame(S::TanpaRilis, S::tentukan(61, null));
    }

    public function test_hanya_tersedia_update_yang_boleh_update(): void
    {
        foreach (S::cases() as $s) {
            $this->assertSame($s === S::TersediaUpdate, $s->bisaUpdate(), $s->value);
        }
    }

    public function test_build_nol_adalah_firmware_lokal_dan_tidak_ditawari_update(): void
    {
        $this->assertSame(S::TanpaNomorBuild, S::tentukan(0, 61));
        $this->assertSame(S::TanpaNomorBuild, S::tentukan(0, null));
        $this->assertSame(false, S::TanpaNomorBuild->bisaUpdate());
    }

    public function test_build_satu_adalah_batas_terendah_yang_bisa_dibandingkan(): void
    {
        $this->assertSame(S::TersediaUpdate, S::tentukan(1, 2));
        $this->assertSame(S::Terbaru, S::tentukan(1, 1));
    }

    public function test_setiap_status_punya_label_tidak_kosong(): void
    {
        foreach (S::cases() as $s) {
            $this->assertTrue($s->label() !== '', $s->value);
        }
    }
}
