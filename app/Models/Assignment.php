<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'school_id',
    'academic_year_id',
    'term_id',
    'school_class_id',
    'class_section_id',
    'subject_id',
    'staff_id',
    'title',
    'instructions',
    'attachment_path',
    'assigned_on',
    'due_on',
    'status',
])]
class Assignment extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected function casts(): array
    {
        return [
            'assigned_on' => 'date',
            'due_on' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
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

    public function confirmations(): HasMany
    {
        return $this->hasMany(AssignmentConfirmation::class);
    }

    public function classLabel(): string
    {
        return collect([
            $this->schoolClass?->name,
            $this->classSection?->name,
        ])->filter()->join(' ');
    }
}
