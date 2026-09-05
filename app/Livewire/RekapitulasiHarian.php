<?php

namespace App\Livewire;

use App\Models\Machine;
use App\Models\ProductionRecap;
use Livewire\Component;
use Livewire\WithPagination;

class RekapitulasiHarian extends Component
{
    use WithPagination;

    public ?int $machineId = null;
    public string $shift = '';
    public string $tanggalDari = '';
    public string $tanggalSampai = '';

    public function mount(?string $machine = null): void
    {
        // Default: hari ini dan 4 hari sebelumnya
        $this->tanggalDari = $this->tanggalDari ?? now()->format('Y-m-d');
        $this->tanggalSampai = $this->tanggalSampai ?? now()->format('Y-m-d');

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
        $recaps = ProductionRecap::query()
            ->with('machine')
            ->when(
                $this->machineId,
                fn($query) =>
                $query->where('machine_id', $this->machineId)
            )
            ->when(
                $this->shift,
                fn($query) =>
                $query->where('shift', $this->shift)
            )
            ->when(
                $this->tanggalDari && $this->tanggalSampai,
                fn($query) =>
                $query->whereBetween('tanggal', [$this->tanggalDari, $this->tanggalSampai])
            )
            ->orderByDesc('tanggal')
            ->paginate(20);

        return view('livewire.rekapitulasi-harian', [
            'recaps' => $recaps,
            'daftarMesin' => Machine::query()->orderBy('nama')->get(),
        ]);
    }
}
