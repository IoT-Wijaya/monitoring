<?php

namespace Tests\Unit;

use App\Support\Ota\InvalidOtaStatusException;
use App\Support\Ota\OtaResult;
use App\Support\Ota\OtaStatusParser;
use PHPUnit\Framework\TestCase;

class OtaStatusParserTest extends TestCase
{
    public function test_laporan_sah_dibaca_lengkap(): void
    {
        $s = OtaStatusParser::parse('{"result":"confirmed","target":5,"previous":4,"build":5}');

        $this->assertSame(OtaResult::Confirmed, $s->result);
        $this->assertSame(5, $s->targetBuild);
        $this->assertSame(4, $s->previousBuild);
        $this->assertSame(5, $s->runningBuild);
    }

    public function test_urutan_kunci_dan_kunci_tambahan_tidak_masalah(): void
    {
        $s = OtaStatusParser::parse('{"extra":"x","build":4,"previous":4,"target":5,"result":"rolled_back_timeout"}');

        $this->assertSame(OtaResult::RolledBackTimeout, $s->result);
        $this->assertSame(4, $s->runningBuild);
    }

    public function test_semua_kode_firmware_dikenal(): void
    {
        foreach (
            [
                'confirmed',
                'rolled_back_timeout',
                'rolled_back_crash_loop',
                'rolled_back_not_booted',
                'rollback_failed',
                'download_failed',
                'verify_failed',
                'write_failed',
                'abandoned'
            ] as $kode
        ) {
            $s = OtaStatusParser::parse('{"result":"' . $kode . '","target":2,"previous":1,"build":1}');
            $this->assertSame($kode, $s->result->value);
        }
    }

    public function test_nilai_build_terbesar_uint32_diterima_dan_di_atasnya_ditolak(): void
    {
        $s = OtaStatusParser::parse('{"result":"confirmed","target":4294967295,"previous":0,"build":4294967295}');
        $this->assertSame(4294967295, $s->targetBuild);
        $this->assertSame(0, $s->previousBuild);

        $this->assertDitolak('{"result":"confirmed","target":4294967296,"previous":0,"build":1}');
    }

    public function test_muatan_salah_ditolak(): void
    {
        $buruk = [
            '',
            'bukan json',
            '[]',
            '[1,2,3]',
            '"confirmed"',
            '123',
            'null',
            '{}',
            '{"result":"confirmed"}',
            '{"result":"confirmed","target":5,"previous":4}',
            '{"result":"hack","target":5,"previous":4,"build":5}',
            '{"result":null,"target":5,"previous":4,"build":5}',
            '{"result":5,"target":5,"previous":4,"build":5}',
            '{"result":"Confirmed","target":5,"previous":4,"build":5}',
            '{"result":"confirmed","target":"5","previous":4,"build":5}',
            '{"result":"confirmed","target":5.5,"previous":4,"build":5}',
            '{"result":"confirmed","target":-1,"previous":4,"build":5}',
            '{"result":"confirmed","target":true,"previous":4,"build":5}',
            '{"result":"confirmed","target":null,"previous":4,"build":5}',
            '{"result":"confirmed","target":[5],"previous":4,"build":5}',
            '{"result":"confirmed","target":5,"previous":4,"build":5',
        ];
        foreach ($buruk as $muatan) {
            $this->assertDitolak($muatan);
        }
    }

    public function test_muatan_terlalu_besar_ditolak_sebelum_diurai(): void
    {
        $this->assertDitolak('{"result":"confirmed","target":5,"previous":4,"build":5,"x":"' . str_repeat('a', 600) . '"}');
    }

    public function test_json_bersarang_terlalu_dalam_ditolak(): void
    {
        $this->assertDitolak('{"result":"confirmed","target":5,"previous":4,"build":5,"x":{"y":{"z":{"w":1}}}}');
    }

    public function test_kategori_dan_label_ada_untuk_setiap_hasil(): void
    {
        foreach (OtaResult::cases() as $hasil) {
            $this->assertContains($hasil->kategori(), ['sukses', 'dikembalikan', 'gagal']);
            $this->assertNotSame('', $hasil->label());
        }
        $this->assertSame('sukses', OtaResult::Confirmed->kategori());
        $this->assertSame('dikembalikan', OtaResult::RolledBackTimeout->kategori());
        $this->assertSame('gagal', OtaResult::RollbackFailed->kategori());
    }

    private function assertDitolak(string $muatan): void
    {
        try {
            OtaStatusParser::parse($muatan);
        } catch (InvalidOtaStatusException) {
            $this->addToAssertionCount(1);

            return;
        }
        $this->fail('seharusnya ditolak: ' . substr($muatan, 0, 80));
    }
}
