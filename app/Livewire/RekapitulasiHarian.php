<?php

namespace App\Livewire;

use App\Exports\RekapProduksiExport;
use App\Models\Machine;
use App\Models\ProductionRecap;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Excel;

class RekapitulasiHarian extends Component
{
    use WithPagination;

    public ?int $machineId = null;
    public string $shift = '';
    public string $tanggalDari = '';
    public string $tanggalSampai = '';

    public function mount(?string $machine = null): void
    {
        // Default: hari ini (diisi langsung, bukan pakai ??)
        $this->tanggalDari = now()->format('Y-m-d');
        $this->tanggalSampai = now()->format('Y-m-d');

        // Jika halaman dibuka dengan parameter mesin dari URL
        if ($machine) {
            $this->machineId = Machine::firstWhere('slug', $machine)?->id;
        }
    }

    /**
     * Reset pagination setiap kali filter berubah.
     */
    public function updated(): void
    {
        $this->resetPage();
    }

    /**
     * Reset seluruh filter.
     */
    public function resetFilter(): void
    {
        $this->machineId = null;
        $this->shift = '';
        $this->tanggalDari = now()->format('Y-m-d');
        $this->tanggalSampai = now()->format('Y-m-d');

        $this->resetPage();

        // Sinkronkan kembali tanggal dengan Flatpickr di sisi frontend
        $this->dispatch(
            'reset-date-range',
            dari: $this->tanggalDari,
            sampai: $this->tanggalSampai
        );
    }

    /**
     * Render halaman.
     */
    public function render()
    {
        // Query dasar dipakai bersama oleh tabel dan total, jadi angkanya selalu cocok
        $query = ProductionRecap::query()
            ->mesin($this->machineId)
            ->shift($this->shift)
            ->rentangTanggal($this->tanggalDari, $this->tanggalSampai);

        $total = (clone $query)
            ->selectRaw('COALESCE(SUM(produksi_s1), 0) as masuk, COALESCE(SUM(produksi_s2), 0) as keluar')
            ->first();

        $recaps = $query
            ->with('machine')
            ->orderByDesc('tanggal')
            ->orderByDesc('jam')
            ->paginate(20);

        return view('livewire.rekapitulasi-harian', [
            'recaps' => $recaps,
            'total' => $total,
            'daftarMesin' => Machine::query()->orderBy('nama')->get(),
        ]);
    }

    public function exportRekap()
    {
        $fileName = 'rekap-produksi-' . date('Y-m-d_H-i-s') . '.xlsx';

        return app(Excel::class)->download(
            new RekapProduksiExport($this->tanggalDari, $this->tanggalSampai, $this->machineId, $this->shift),
            $fileName
        );
    }
}
