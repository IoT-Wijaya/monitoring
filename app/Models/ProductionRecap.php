<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;


class ProductionRecap extends Model
{
    protected $fillable = [
        'machine_id',
        'tanggal',
        'jam',
        'shift',
        'sensor1_masuk',
        'sensor2_keluar',
        'produksi_s1',
        'produksi_s2',
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

    public function scopeShift(Builder $query, string $shift): Builder
    {
        // Ambil angka jam (0-23) dari kolom `jam`, apa pun formatnya:
        // 6, '6', '06:00', '06:00:00' semuanya menjadi 6
        $jam = 'CAST(SUBSTRING(jam, 1, 2) AS UNSIGNED)';

        if ($shift === 'Shift Pagi') {
            // 06:00 - 16:59
            return $query->whereRaw("$jam BETWEEN 6 AND 16");
        }

        // Shift Malam: 17:00 - 23:59 dan 00:00 - 05:59 (melewati tengah malam)
        return $query->whereRaw("($jam >= 17 OR $jam < 6)");
    }

    public function scopeRentangTanggal(Builder $query, ?string $dari, ?string $sampai): Builder
    {
        return $query
            ->when($dari, fn($q) => $q->whereDate('tanggal', '>=', $dari))
            ->when($sampai, fn($q) => $q->whereDate('tanggal', '<=', $sampai));
    }

    protected function jamLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (is_numeric($this->jam)) {
                    $mulai = (int) $this->jam;
                    return sprintf('%02d:00 - %02d:00', $mulai, ($mulai + 1) % 24);
                }
                return substr((string) $this->jam, 0, 5);
            }
        );
    }
}
