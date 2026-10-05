<?php

namespace App\Exports;

use App\Models\ProductionRecap;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RekapProduksiExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $tanggalDari;
    protected $tanggalSampai;
    protected $machineId;
    protected $shift;

    // Nomor urut baris, dihitung per export
    protected int $nomor = 0;

    public function __construct($tanggalDari, $tanggalSampai, $machineId, $shift)
    {
        $this->tanggalDari = $tanggalDari;
        $this->tanggalSampai = $tanggalSampai;
        $this->machineId = $machineId;
        $this->shift = $shift;
    }

    public function collection(): Collection
    {
        return ProductionRecap::query()
            ->mesin($this->machineId)
            ->shift($this->shift)
            ->rentangTanggal($this->tanggalDari, $this->tanggalSampai)
            ->with('machine')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Jam',
            'Nama Mesin',
            'Status Shift',
            'Masuk (pcs)',
            'Keluar (pcs)',
            'Selisih (pcs)',
        ];
    }

    public function map($row): array
    {
        $this->nomor++;

        $masuk = (int) $row->produksi_s1;
        $keluar = (int) $row->produksi_s2;

        return [
            $this->nomor,
            $row->tanggal->format('d-m-Y'),
            $row->jam_label,
            $row->machine->nama ?? '-',
            $row->shift,
            $masuk,
            $keluar,
            $keluar - $masuk,
        ];
    }
}
