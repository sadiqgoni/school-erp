<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'title',
    'body',
    'attachment_path',
    'audience_type',
    'audience_division',
    'school_class_id',
    'class_section_id',
    'category',
    'is_pinned',
    'status',
    'published_at',
    'expires_on',
    'created_by',
])]
class Notice extends Model
{
    public const AUDIENCE_ALL = 'all';

    public const AUDIENCE_DIVISION = 'division';

    public const AUDIENCE_CLASS = 'class';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
            'expires_on' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where(fn (Builder $query) => $query
                ->whereNull('expires_on')
                ->orWhereDate('expires_on', '>=', today()));
    }

    public function audienceLabel(): string
    {
        return match ($this->audience_type) {
            self::AUDIENCE_DIVISION => $this->audience_division ?: 'Division',
            self::AUDIENCE_CLASS => collect([
                $this->schoolClass?->name,
                $this->classSection?->name,
            ])->filter()->join(' ') ?: 'Class',
            default => 'Whole school',
        };
    }
}
