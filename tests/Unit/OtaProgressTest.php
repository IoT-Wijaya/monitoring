<?php

namespace Tests\Unit;

use App\Support\Ota\OtaFase;
use App\Support\Ota\OtaProgress;
use App\Support\Ota\OtaResult;
use PHPUnit\Framework\TestCase;

class OtaProgressTest extends TestCase
{
    private const T0 = 1000000; // waktu perintah dikirim
    private const TARGET = 61;

    /** Keadaan dasar: baru dikirim, mesin masih online dengan build lama, belum ada laporan. */
    private function fase(array $o = []): OtaFase
    {
        $o += [
            'sekarang' => self::T0 + 5,
            'offline' => false,
            'online' => true,
            'build' => 10,
            'hasil' => null,
            'targetLap' => null,
            'lapPada' => null,
            'batas' => 900,
        ];

        return OtaProgress::tentukan(
            self::TARGET,
            self::T0,
            $o['sekarang'],
            $o['offline'],
            $o['online'],
            $o['build'],
            $o['hasil'],
            $o['targetLap'],
            $o['lapPada'],
            $o['batas']
        );
    }

    public function test_baru_dikirim_mesin_masih_online_build_lama(): void
    {
        $this->assertSame(OtaFase::Terkirim, $this->fase());
    }

    public function test_mesin_terlihat_offline_berarti_sedang_memasang(): void
    {
        $this->assertSame(OtaFase::Memasang, $this->fase(['offline' => true, 'online' => false]));
    }

    public function test_mesin_online_kembali_dengan_build_lama_masih_dianggap_memasang(): void
    {
        $this->assertSame(OtaFase::Memasang, $this->fase(['offline' => true, 'online' => true, 'build' => 10]));
    }

    public function test_build_target_berjalan_menunggu_konfirmasi_sehat(): void
    {
        $f = $this->fase(['offline' => true, 'online' => true, 'build' => self::TARGET]);

        $this->assertSame(OtaFase::FirmwareBaruBerjalan, $f);
    }

    public function test_build_target_terlihat_tanpa_pernah_melihat_offline_tetap_terdeteksi(): void
    {
        // Restart secepat satu siklus polling: offline tidak sempat terlihat.
        $this->assertSame(OtaFase::FirmwareBaruBerjalan, $this->fase(['offline' => false, 'build' => self::TARGET]));
    }

    public function test_build_target_tetapi_mesin_offline_tidak_dianggap_berjalan(): void
    {
        $this->assertSame(OtaFase::Memasang, $this->fase(['offline' => true, 'online' => false, 'build' => self::TARGET]));
    }

    public function test_laporan_confirmed_untuk_target_ini_berarti_berhasil(): void
    {
        $f = $this->fase(['hasil' => OtaResult::Confirmed, 'targetLap' => self::TARGET, 'lapPada' => self::T0 + 120]);

        $this->assertSame(OtaFase::Berhasil, $f);
    }

    public function test_semua_kode_rollback_berarti_dikembalikan(): void
    {
        foreach ([OtaResult::RolledBackTimeout, OtaResult::RolledBackCrashLoop, OtaResult::RolledBackNotBooted] as $h) {
            $f = $this->fase(['hasil' => $h, 'targetLap' => self::TARGET, 'lapPada' => self::T0 + 60]);
            $this->assertSame(OtaFase::Dikembalikan, $f, $h->value);
        }
    }

    public function test_semua_kode_gagal_berarti_gagal(): void
    {
        foreach (
            [
                OtaResult::RollbackFailed,
                OtaResult::DownloadFailed,
                OtaResult::VerifyFailed,
                OtaResult::WriteFailed,
                OtaResult::Abandoned
            ] as $h
        ) {
            $f = $this->fase(['hasil' => $h, 'targetLap' => self::TARGET, 'lapPada' => self::T0 + 60]);
            $this->assertSame(OtaFase::Gagal, $f, $h->value);
        }
    }

    public function test_laporan_lama_sebelum_perintah_diabaikan(): void
    {
        // Retained dari uji sebelumnya: diterima sebelum perintah ini dikirim.
        $f = $this->fase(['hasil' => OtaResult::VerifyFailed, 'targetLap' => self::TARGET, 'lapPada' => self::T0 - 1]);

        $this->assertSame(OtaFase::Terkirim, $f);
    }

    public function test_laporan_untuk_target_lain_diabaikan(): void
    {
        $f = $this->fase(['hasil' => OtaResult::VerifyFailed, 'targetLap' => 6, 'lapPada' => self::T0 + 30]);

        $this->assertSame(OtaFase::Terkirim, $f);
    }

    public function test_laporan_tanpa_waktu_atau_target_diabaikan(): void
    {
        $this->assertSame(OtaFase::Terkirim, $this->fase(['hasil' => OtaResult::Confirmed, 'targetLap' => self::TARGET, 'lapPada' => null]));
        $this->assertSame(OtaFase::Terkirim, $this->fase(['hasil' => OtaResult::Confirmed, 'targetLap' => null, 'lapPada' => self::T0 + 9]));
    }

    public function test_laporan_pada_detik_yang_sama_dengan_perintah_dihitung(): void
    {
        $f = $this->fase(['hasil' => OtaResult::Confirmed, 'targetLap' => self::TARGET, 'lapPada' => self::T0]);

        $this->assertSame(OtaFase::Berhasil, $f);
    }

    public function test_tanpa_laporan_setelah_batas_waktu_berarti_tanpa_kabar(): void
    {
        $this->assertSame(OtaFase::TanpaKabar, $this->fase(['sekarang' => self::T0 + 901]));
        $this->assertSame(OtaFase::Terkirim, $this->fase(['sekarang' => self::T0 + 900]));
    }

    public function test_laporan_menang_atas_batas_waktu(): void
    {
        $f = $this->fase([
            'sekarang' => self::T0 + 5000,
            'hasil' => OtaResult::Confirmed,
            'targetLap' => self::TARGET,
            'lapPada' => self::T0 + 200,
        ]);

        $this->assertSame(OtaFase::Berhasil, $f);
    }

    public function test_fase_berurutan_dan_kemajuan_tidak_pernah_turun(): void
    {
        $urut = [OtaFase::Terkirim, OtaFase::Memasang, OtaFase::FirmwareBaruBerjalan, OtaFase::Berhasil];
        $sebelum = -1;
        foreach ($urut as $f) {
            $this->assertTrue($f->persen() > $sebelum, $f->value);
            $sebelum = $f->persen();
        }
        $this->assertSame(100, OtaFase::Berhasil->persen());
        $this->assertSame(100, OtaFase::Gagal->persen());
    }

    public function test_hanya_fase_akhir_dianggap_selesai(): void
    {
        foreach (OtaFase::cases() as $f) {
            $akhir = in_array($f, [OtaFase::Berhasil, OtaFase::Dikembalikan, OtaFase::Gagal, OtaFase::TanpaKabar], true);
            $this->assertSame($akhir, $f->selesai(), $f->value);
        }
    }

    public function test_setiap_fase_punya_teks_nada_dan_langkah_yang_sah(): void
    {
        foreach (OtaFase::cases() as $f) {
            $this->assertTrue($f->judul() !== '' && $f->keterangan() !== '', $f->value);
            $this->assertContains($f->nada(), ['berjalan', 'sukses', 'peringatan', 'galat']);
            $this->assertTrue($f->langkahAktif() >= 0 && $f->langkahAktif() < count(OtaFase::LANGKAH), $f->value);
        }
    }
}
