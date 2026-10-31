<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class FirmwareRelease extends Model
{
    protected $fillable = [
        'device_type',
        'version',
        'download_url',
        'release_notes',
        'checksum_sha256',
        'is_active',
        'released_at',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'released_at' => 'datetime',
    ];

    // Pemakaian: FirmwareRelease::active()->latest('released_at')->first();
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function localPath(): string
    {
        return storage_path('app/firmware/' . $this->device_type . '-' . $this->version . '.bin');
    }
}
