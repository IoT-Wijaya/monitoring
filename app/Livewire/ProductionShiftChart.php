<?php

namespace App\Livewire;

use App\Models\Machine;
use App\Models\ProductionRecap;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class ProductionShiftChart extends ChartWidget
{
    public ?int $machineId = null;

    protected ?string $pollingInterval = '10s';

    public ?string $filter = 'today';

    public function getHeading(): string|Htmlable|null
    {
        $machine = Machine::find($this->machineId);

        return $machine?->nama ?? 'Mesin tidak ditemukan';
    }

    protected function getFilters(): ?array
    {
        return [
            'today'     => 'Hari Ini (' . Carbon::today()->format('d M Y') . ')',
            'yesterday' => 'Kemarin (' . Carbon::yesterday()->format('d M Y') . ')',
            'custom_7'  => '7 Hari Terakhir',
        ];
    }

    protected function getData(): array
    {
        $mulai   = Carbon::today();
        $selesai = Carbon::today();

        if ($this->filter === 'yesterday') {
            $mulai = $selesai = Carbon::yesterday();
        } elseif ($this->filter === 'custom_7') {
            $mulai = Carbon::today()->subDays(6);
        }

        $labels = [];
        $pagi   = [];
        $malam  = [];

        foreach (CarbonPeriod::create($mulai, $selesai) as $tanggal) {
            $tgl = $tanggal->toDateString();

            $labels[] = $tanggal->format('d M');

            $pagi[] = (int) ProductionRecap::mesin($this->machineId)
                ->rentangTanggal($tgl, $tgl)
                ->shift('Shift Pagi')
                ->sum('produksi_s1');

            $malam[] = (int) ProductionRecap::mesin($this->machineId)
                ->rentangTanggal($tgl, $tgl)
                ->shift('Shift Malam')
                ->sum('produksi_s1');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Shift Pagi (06:00 - 16:00)',
                    'data' => $pagi,
                    'backgroundColor' => '#3b82f6',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Shift Malam (17:00 - 06:00)',
                    'data' => $malam,
                    'backgroundColor' => '#8b5cf6',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
