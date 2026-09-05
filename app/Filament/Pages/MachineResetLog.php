<?php

namespace App\Filament\Pages;

use App\Models\MachineResetLog as MachineResetLogModel;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use UnitEnum;

class MachineResetLog extends Page
{
    protected static ?string $navigationLabel = 'Log Reset';
    protected static ?string $title = 'Machine Reset Logs';
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static UnitEnum|string|null $navigationGroup = 'Riwayat';

    protected string $view = 'filament.pages.machine-reset-log';

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public ?string $tanggalDari = null;
    public ?string $tanggalSampai = null;
    public ?string $machineId = null;
    public ?string $targetReset = null;

    public function mount(): void
    {
        $this->tanggalDari = $this->tanggalDari ?? now()->format('Y-m-d');
        $this->tanggalSampai = $this->tanggalSampai ?? now()->format('Y-m-d');
    }

    public function resetFilters(): void
    {
        $this->reset(['machineId', 'targetReset']);
        $this->tanggalDari = now()->subDays(30)->format('Y-m-d');
        $this->tanggalSampai = now()->format('Y-m-d');

        $this->dispatch('reset-date-range', dari: $this->tanggalDari, sampai: $this->tanggalSampai);
    }

    public function getLogsProperty(): LengthAwarePaginator
    {
        return MachineResetLogModel::query()
            ->with(['machine', 'user'])
            ->when($this->machineId, fn($q) => $q->where('machine_id', $this->machineId))
            ->when($this->targetReset, fn($q) => $q->where('target', $this->targetReset))
            ->when($this->tanggalDari && $this->tanggalSampai, function ($q) {
                $q->whereBetween('created_at', [
                    $this->tanggalDari . ' 00:00:00',
                    $this->tanggalSampai . ' 23:59:59'
                ]);
            })
            ->latest('created_at')
            ->paginate(15);
    }
}
