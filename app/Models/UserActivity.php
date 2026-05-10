<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'user_id',
    'school_id',
    'action',
    'description',
    'auditable_type',
    'auditable_id',
    'old_values',
    'new_values',
    'panel',
    'url',
    'ip_address',
    'user_agent',
])]
class UserActivity extends Model
{
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
