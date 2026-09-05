<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $fillable = ['nama', 'kode_esp32', 'slug'];

    /**
     * Relasi 1:banyak -> histori rekap (halaman Rekapitulasi baca dari sini).
     */
    public function recaps()
    {
        return $this->hasMany(ProductionRecap::class);
    }
}
