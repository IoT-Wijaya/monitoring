<?php

namespace App\Filament\Resources\MaintenanceLogs\Schemas;

use App\Forms\Components\CompressedFileUpload;
use App\Models\Machine;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MaintenanceLogForm
{
    protected static function buildWatermark(string $label, Get $get): string
    {
        // Nama mesin dibatasi 25 karakter agar teks tidak melebihi lebar foto
        $machine = Str::limit(Machine::find($get('machine_id'))?->nama ?? '-', 25);

        // Nama teknisi dari user yang sedang login (ganti ke ->username jika perlu)
        $technician = Auth::user()?->name ?? '-';

        return implode(' | ', [
            $label,
            $machine,
            now()->format('d/m/Y H:i') . ' WIB',
            $technician,
        ]);
    }


    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal')
                    ->label('Pilih Tanggal Laporan')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->maxDate(now())
                    ->default(now())
                    ->live()
                    ->closeOnDateSelection()
                    ->suffixIcon('heroicon-o-calendar')
                    ->suffixIconColor('primary')
                    ->columnSpanFull()
                    ->required(),

                Select::make('machine_id')
                    ->label('Pilih Mesin')
                    ->options(Machine::all()->pluck('nama', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),

                Select::make('kendala')
                    ->label('Jenis Kendala')
                    ->options([
                        'Pemeliharaan Terjadwal' => 'Pemeliharaan Terjadwal',
                        'Perbaikan Darurat' => 'Perbaikan Darurat',
                        'Instalasi Perangkat Baru' => 'Instalasi Perangkat Baru',
                        'Gangguan Perangkat' => 'Gangguan Perangkat',
                        'Peningkatan Sistem' => 'Peningkatan Sistem',
                    ])
                    ->required()
                    ->native(false)
                    ->columnSpanFull(),

                CompressedFileUpload::make('foto_sebelum')
                    ->label('Foto Sebelum Perbaikan')
                    ->directory('maintenance/before')
                    ->disk('public')
                    ->visibility('public')
                    ->imageEditor()
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('16:9')
                    ->imageResizeTargetWidth('1280')
                    ->imageResizeTargetHeight('720')
                    ->compressTo(1280, 720, 75)
                    ->watermark(fn(Get $get) => self::buildWatermark('SEBELUM', $get))
                    ->fileName(fn(Get $get) => 'sebelum-' . $get('machine_id') . '-' . now()->format('Ymd-His'))
                    ->required(),

                CompressedFileUpload::make('foto_sesudah')
                    ->label('Foto Sesudah Perbaikan')
                    ->directory('maintenance/after')
                    ->disk('public')
                    ->visibility('public')
                    ->imageEditor()
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('16:9')
                    ->imageResizeTargetWidth('1280')
                    ->imageResizeTargetHeight('720')
                    ->compressTo(1280, 720, 75)
                    ->watermark(fn(Get $get) => static::buildWatermark('SESUDAH', $get))
                    ->fileName(fn(Get $get) => 'sesudah-' . $get('machine_id') . '-' . now()->format('Ymd-His'))
                    ->required(),

                Textarea::make('catatan')
                    ->label('Keterangan / Tindakan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
