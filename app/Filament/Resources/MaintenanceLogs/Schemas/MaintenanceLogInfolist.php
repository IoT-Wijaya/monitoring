<?php

namespace App\Filament\Resources\MaintenanceLogs\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pemeliharaan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('machine.nama')
                            ->label('Mesin'),

                        TextEntry::make('kendala')
                            ->label('Jenis Kendala')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'Perbaikan Darurat', 'Gangguan Perangkat' => 'danger',
                                'Pemeliharaan Terjadwal' => 'info',
                                'Instalasi Perangkat Baru', 'Peningkatan Sistem' => 'success',
                                default => 'gray',
                            }),

                        TextEntry::make('catatan')
                            ->label('Keterangan / Tindakan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Dokumentasi Foto')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('foto_sebelum')
                            ->label('Foto Sebelum Perbaikan')
                            ->disk('public')
                            ->extraImgAttributes(['loading' => 'lazy'])
                            ->placeholder('-'),

                        ImageEntry::make('foto_sesudah')
                            ->label('Foto Sesudah Perbaikan')
                            ->disk('public')
                            ->extraImgAttributes(['loading' => 'lazy'])
                            ->placeholder('-'),
                    ]),

                Section::make('Riwayat Data')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('tanggal')
                            ->label('Tanggal Pekerjaan')
                            ->dateTime('d M Y, H:i'),

                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
