<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'school_class_id',
    'class_section_id',
    'day_of_week',
    'period_number',
    'starts_at',
    'ends_at',
    'subject_id',
    'staff_id',
    'label',
    'entry_type',
])]
class TimetableEntry extends Model
{
    public const TYPE_LESSON = 'lesson';

    public const TYPE_BREAK = 'break';

    public const DAYS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'period_number' => 'integer',
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function dayName(): string
    {
        return self::DAYS[$this->day_of_week] ?? 'Day '.$this->day_of_week;
    }

    public function displayLabel(): string
    {
        if ($this->entry_type === self::TYPE_BREAK) {
            return $this->label ?: 'Break';
        }

        return $this->subject?->name ?: ($this->label ?: 'Lesson');
    }

    public function timeRange(): ?string
    {
        if (! $this->starts_at || ! $this->ends_at) {
            return null;
        }

        return substr($this->starts_at, 0, 5).' - '.substr($this->ends_at, 0, 5);
    }
}
