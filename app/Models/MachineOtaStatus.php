<?php

namespace App\Models;

use App\Support\Ota\OtaResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineOtaStatus extends Model
{
    protected $fillable = [
        'machine_id',
        'result',
        'target_build',
        'previous_build',
        'running_build',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'result' => OtaResult::class,
            'target_build' => 'integer',
            'previous_build' => 'integer',
            'running_build' => 'integer',
            'reported_at' => 'datetime',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
