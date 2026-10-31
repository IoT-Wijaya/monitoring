<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $fillable = [
        'nama',
        'mac_address',
        'slug',
        'device_type',
        'current_version',
        'last_seen_at',
        'last_ota_status',
        'last_ota_message',
        'last_ota_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'last_ota_at'  => 'datetime',
    ];

    // Seragamkan format MAC setiap kali diisi
    public function setMacAddressAttribute(?string $value): void
    {
        $this->attributes['mac_address'] = $value ? strtoupper(trim($value)) : null;
    }

    /**
     * Relasi 1:banyak -> histori rekap (halaman Rekapitulasi baca dari sini).
     */
    public function recaps()
    {
        return $this->hasMany(ProductionRecap::class);
    }

    public function maintenanceLogs()
    {
        return $this->hasMany(MaintenanceLog::class);
    }
}
