<?php

namespace App\Console\Commands;

use App\Models\Machine;
use App\Services\MachineCounterService;
use Illuminate\Console\Command;

class SnapshotRekapPerJam extends Command
{
    protected $signature = 'rekap:snapshot-jam';
    protected $description = 'Snapshot angka counter tiap mesin ke rekap per jam (dipanggil scheduler tiap jam)';

    public function handle(MachineCounterService $counter): int
    {
        Machine::all()->each(function (Machine $machine) use ($counter) {
            $counter->snapshotPerJam($machine);
            $this->info("Snapshot tersimpan: {$machine->slug} jam " . now()->format('H') . ":00");
        });

        return self::SUCCESS;
    }
}
