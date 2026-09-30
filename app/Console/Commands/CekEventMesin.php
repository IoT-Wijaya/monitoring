<?php

namespace App\Console\Commands;

use App\Models\Machine;
use App\Services\MachineEventService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:cek-event-mesin')]
#[Description('Command description')]
class CekEventMesin extends Command
{
    /**
     * Execute the console command.
     */
    protected $signature = 'mesin:cek-event';
    protected $description = 'Catat event offline/online dan error sensor tiap mesin (dijadwalkan tiap 10 detik)';

    public function handle(MachineEventService $events): int
    {
        Machine::all()->each(function (Machine $machine) use ($events) {
            try {
                $events->periksa($machine);
            } catch (\Throwable $e) {
                report($e); // satu mesin bermasalah tidak menghentikan mesin lain
            }
        });

        return self::SUCCESS;
    }
}
