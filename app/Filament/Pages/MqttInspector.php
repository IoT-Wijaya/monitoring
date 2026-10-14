<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use UnitEnum;

class MqttInspector extends Page
{
    // Halaman ini bisa MENGIRIM perintah ke mesin lewat MQTT, jadi akses dibatasi lewat Shield.
    // Setelah file ini ada, jalankan: php artisan shield:generate --all --panel=admin
    // lalu beri permission "page_MqttInspector" ke role yang boleh memakainya.
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'MQTT Inspector';

    protected static string|UnitEnum|null $navigationGroup = 'Maintenance';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'MQTT Web Inspector';   // static

    protected string $view = 'filament.pages.mqtt-inspector'; // non-static
}
