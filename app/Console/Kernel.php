<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Perintah Artisan kustom yang didaftarkan secara manual.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\MqttSubscribe::class,
    ];

    /**
     * Mendefinisikan jadwal tugas/cron job aplikasi.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Contoh jika ingin menjalankan task terjadwal di kemudian hari
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Memuat file command kustom dari direktori Commands dan console.php.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
