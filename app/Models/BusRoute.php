<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'school_id',
    'name',
    'code',
    'vehicle_name',
    'plate_number',
    'driver_name',
    'driver_phone',
    'assistant_name',
    'assistant_phone',
    'capacity',
    'is_active',
])]
class BusRoute extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(BusRouteStudent::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StudentMovement::class);
    }
}
