<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('rekap:snapshot-jam')
    ->hourly()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/snapshot-jam.log'));

Schedule::command('mesin:cek-event')
    ->everyTenSeconds()
    ->withoutOverlapping();
