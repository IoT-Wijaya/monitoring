<?php

namespace App\Filament\Resources\MaintenanceLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class MaintenanceLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('machine.nama')
                    ->label('Mesin')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kendala')
                    ->label('Jenis Kendala')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Perbaikan Darurat', 'Gangguan Perangkat' => 'danger',
                        'Pemeliharaan Terjadwal' => 'info',
                        'Instalasi Perangkat Baru', 'Peningkatan Sistem' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),

                ImageColumn::make('foto_sebelum')
                    ->label('Foto Sebelum')
                    ->disk('public')
                    ->imageHeight(50)
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->url(fn($record): ?string => $record->foto_sebelum ? Storage::disk('public')->url($record->foto_sebelum) : null)
                    ->openUrlInNewTab(),

                ImageColumn::make('foto_sesudah')
                    ->label('Foto Sesudah')
                    ->disk('public')
                    ->imageHeight(50)
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->url(fn($record): ?string => $record->foto_sesudah ? Storage::disk('public')->url($record->foto_sesudah) : null)
                    ->openUrlInNewTab(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('machine_id')
                    ->label('Mesin')
                    ->relationship('machine', 'nama')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('kendala')
                    ->label('Jenis Kendala')
                    ->options([
                        'Pemeliharaan Terjadwal' => 'Pemeliharaan Terjadwal',
                        'Perbaikan Darurat' => 'Perbaikan Darurat',
                        'Instalasi Perangkat Baru' => 'Instalasi Perangkat Baru',
                        'Gangguan Perangkat' => 'Gangguan Perangkat',
                        'Peningkatan Sistem' => 'Peningkatan Sistem',
                    ]),
            ])
            ->recordActions([
                // ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
