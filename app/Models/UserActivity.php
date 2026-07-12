<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
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
    use Concerns\BelongsToSchool;
    use Prunable;

    /**
     * How long an activity row is kept before `php artisan model:prune`
     * removes it. The audit trail is for recent trust/dispute questions,
     * not permanent archival — without this the table grows forever
     * across every school and eventually dominates the database.
     */
    public const RETENTION_DAYS = 90;

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }

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
