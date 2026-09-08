<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MachineEvent extends Model
{
    protected $fillable = ['machine_id', 'jenis', 'status', 'keterangan', 'terjadi_pada'];

    protected $casts = [
        'terjadi_pada' => 'datetime',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }


    public function scopeMesin(Builder $query, ?int $machineId): Builder
    {
        return $query->when($machineId, fn($q) => $q->where('machine_id', $machineId));
    }

    public function scopeJenis(Builder $query, ?string $jenis): Builder
    {
        return $query->when($jenis, fn($q) => $q->where('jenis', $jenis));
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, fn($q) => $q->where('status', $status));
    }

    public function scopeRentangTanggal(Builder $query, ?string $dari, ?string $sampai): Builder
    {
        return $query
            ->when($dari, fn($q) => $q->whereDate('terjadi_pada', '>=', $dari))
            ->when($sampai, fn($q) => $q->whereDate('terjadi_pada', '<=', $sampai));
    }
}
