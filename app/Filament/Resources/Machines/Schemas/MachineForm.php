<?php

namespace App\Filament\Resources\Machines\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MachineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->required(),
                TextInput::make('kode_esp32')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
            ]);
    }
}
