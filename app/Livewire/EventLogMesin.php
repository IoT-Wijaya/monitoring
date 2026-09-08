<?php

namespace App\Livewire;

use App\Models\Machine;
use App\Models\MachineEvent;
use Livewire\Component;
use Livewire\WithPagination;

class EventLogMesin extends Component
{
    use WithPagination;
    public string $tanggalDari = '';
    public string $tanggalSampai = '';
    public ?int $machineId = null;
    public string $jenis = '';
    public string $status = '';

    public function mount(): void
    {
        $this->tanggalDari = now()->toDateString();
        $this->tanggalSampai = now()->toDateString();
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['machineId', 'jenis', 'status']);
        $this->tanggalDari = now()->toDateString();
        $this->tanggalSampai = now()->toDateString();
    }

    public function getLogsProperty()
    {
        return MachineEvent::query()
            ->with('machine')
            ->mesin($this->machineId)
            ->jenis($this->jenis ?: null)
            ->status($this->status ?: null)
            ->rentangTanggal($this->tanggalDari, $this->tanggalSampai)
            ->orderByDesc('terjadi_pada')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.event-log-mesin', [
            'daftarMesin' => Machine::orderBy('nama')->get(),
        ]);
    }
}
