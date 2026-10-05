<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    use HasUuids;
    protected $fillable = [
        'tanggal',
        'machine_id',
        'kendala',
        'foto_sebelum',
        'foto_sesudah',
        'catatan',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    // Pakai uuid, bukan id, saat model dicari lewat URL
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'tanggal' => 'datetime',
        ];
    }

    /**
     * Relasi ke model Machine
     */
    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
