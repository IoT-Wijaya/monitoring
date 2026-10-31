<?php

namespace App\Filament\Resources\Machines;

use App\Filament\Resources\Machines\Pages\CreateMachine;
use App\Filament\Resources\Machines\Pages\EditMachine;
use App\Filament\Resources\Machines\Pages\ListMachines;
use App\Filament\Resources\Machines\Pages\ViewMachine;
use App\Filament\Resources\Machines\Schemas\MachineForm;
use App\Filament\Resources\Machines\Schemas\MachineInfolist;
use App\Filament\Resources\Machines\Tables\MachinesTable;
use App\Models\Machine;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MachineResource extends Resource
{
    protected static ?string $model = Machine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;
    protected static UnitEnum|string|null $navigationGroup = 'Master';
    protected static ?int $navigationSort = 2;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected static ?string $recordTitleAttribute = 'Machine';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('nama')
                ->label('Nama Mesin')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->required(),
            TextInput::make('mac_address')
                ->label('MAC Address ESP32')
                ->placeholder('Contoh: AA:BB:CC:DD:EE:FF')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),
            TextInput::make('slug')
                ->required()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('device_type')
                ->label('Tipe Perangkat')
                ->default('ESP32')
                ->maxLength(100),

            TextInput::make('current_version')
                ->label('Versi Firmware Saat Ini')
                ->placeholder('v1.0.0')
                ->maxLength(50),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MachineInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Mesin')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('mac_address')
                    ->label('MAC Address ESP32')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('device_type')
                    ->label('Tipe')
                    ->searchable()
                    ->badge(),

                TextColumn::make('current_version')
                    ->label('Versi Firmware')
                    ->searchable(),

                TextColumn::make('last_seen_at')
                    ->label('Terakhir Aktif')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMachines::route('/'),
            'create' => CreateMachine::route('/create'),
            'view' => ViewMachine::route('/{record}'),
            'edit' => EditMachine::route('/{record}/edit'),
        ];
    }
}
