<?php

namespace App\Exports;

use App\Models\ProductionRecap;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RekapProduksiExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $tanggalDari;
    protected $tanggalSampai;
    protected $machineId;
    protected $shift;

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
            ->when($this->tanggalDari && $this->tanggalSampai, function ($q) {
                $q->whereBetween('tanggal', [$this->tanggalDari, $this->tanggalSampai]);
            })
            ->when($this->machineId, function ($q) {
                $q->where('machine_id', $this->machineId);
            })
            ->when($this->shift, function ($q) {
                $q->where('shift', $this->shift);
            })
            ->with('machine')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Nama Mesin',
            'Status Shift',
            'Masuk (pcs)',
            'Keluar (pcs)',
            'Selisih (pcs)'
        ];
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;
        $selisih = $row->sensor2_keluar - $row->sensor1_masuk;

        return [
            $index,
            $row->tanggal,
            $row->machine->nama ?? '-',
            $row->shift,
            $row->sensor1_masuk,
            $row->sensor2_keluar,
            $selisih
        ];
    }
}
