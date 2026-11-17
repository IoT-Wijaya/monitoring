<?php

namespace App\Providers;

use Filament\Pages\Auth\Login as FilamentLogin;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Support\Ota\FirmwareVersionStore::class,
            \App\Support\Ota\CacheFirmwareVersionStore::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
