<?php

use App\Http\Controllers\Api\DeviceFirmwareController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Middleware\EnsureDeviceApiKey;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/github-release', [WebhookController::class, 'handleGitHubRelease'])
    ->middleware('throttle:60,1') // maksimal 60 request per menit
    ->name('webhook.github-release');

Route::middleware([EnsureDeviceApiKey::class, 'throttle:30,1'])
    ->prefix('device')
    ->group(function () {
        Route::get('/firmware/latest', [DeviceFirmwareController::class, 'latest'])
            ->name('device.firmware.latest');
        Route::post('/firmware/report', [DeviceFirmwareController::class, 'report'])
            ->name('device.firmware.report');
        Route::get('/firmware/download', [DeviceFirmwareController::class, 'download'])
            ->name('device.firmware.download');
    });
