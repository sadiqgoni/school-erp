<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'school_id',
    'user_id',
    'name',
    'phone',
    'alternate_phone',
    'email',
    'occupation',
    'address',
    'is_active',
])]
class Guardian extends Model
{
    use HasFactory;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studentLinks(): HasMany
    {
        return $this->hasMany(GuardianStudent::class);
    }

    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class);
    }
}
