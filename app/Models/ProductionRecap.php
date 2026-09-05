<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class ProductionRecap extends Model
{
    protected $fillable = [
        'machine_id',
        'tanggal',
        'jam',
        'shift',
        'sensor1_masuk',
        'sensor2_keluar',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    protected function selisih(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn() => $this->sensor2_keluar - $this->sensor1_masuk,
        );
    }

    /**
     * --- SCOPE = potongan query yang bisa dipakai ulang ---
     * Masing-masing scope di bawah ini adalah representasi LANGSUNG dari
     * satu filter di halaman Rekapitulasi Harian (HTML kamu). Livewire
     * tinggal chaining method ini, tidak perlu tahu detail nama kolom.
     */

    public function scopeMesin(Builder $query, ?int $machineId): Builder
    {
        return $query->when($machineId, fn($q) => $q->where('machine_id', $machineId));
    }

    public function scopeShift(Builder $query, ?string $shift): Builder
    {
        return $query->when($shift, fn($q) => $q->where('shift', $shift));
    }

    public function scopeRentangTanggal(Builder $query, ?string $dari, ?string $sampai): Builder
    {
        return $query
            ->when($dari, fn($q) => $q->whereDate('tanggal', '>=', $dari))
            ->when($sampai, fn($q) => $q->whereDate('tanggal', '<=', $sampai));
    }
}
