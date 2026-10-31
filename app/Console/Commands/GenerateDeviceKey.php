<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('device:key {mac : MAC dari Serial Monitor, contoh A2:B7:65:12:34:56}')]
#[Description('Hitung key OTA untuk satu perangkat')]
class GenerateDeviceKey extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $mac    = strtoupper(trim($this->argument('mac')));
        $master = (string) config('services.device.master_secret');

        if (!preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $mac)) {
            $this->error('Format MAC salah.');
            return self::FAILURE;
        }
        if ($master === '') {
            $this->error('DEVICE_MASTER_SECRET belum diisi di .env');
            return self::FAILURE;
        }

        $this->info('MAC     : ' . $mac);
        $this->info('OTA key : ' . hash_hmac('sha256', $mac, $master));

        return self::SUCCESS;
    }
}
