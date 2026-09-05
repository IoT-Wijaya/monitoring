<?php

namespace App\Filament\Resources\Machines\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MachineInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nama'),
                TextEntry::make('kode_esp32'),
                TextEntry::make('slug'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
