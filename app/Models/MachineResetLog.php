<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MachineResetLog extends Model
{
    protected $fillable = [
        'machine_id',
        'user_id',
        'target',
        'jumlah_s1_sebelum_reset',
        'jumlah_s2_sebelum_reset',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
