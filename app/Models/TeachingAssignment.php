<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'staff_id',
    'academic_year_id',
    'term_id',
    'school_class_id',
    'class_section_id',
    'assignment_role',
    'subject_id',
    'is_class_teacher',
    'is_active',
])]
class TeachingAssignment extends Model
{
    use HasFactory;

    public const ROLE_FORM_TEACHER = 'form_teacher';

    public const ROLE_ASSISTANT_FORM_TEACHER = 'assistant_form_teacher';

    public const ROLE_SUBJECT_TEACHER = 'subject_teacher';

    protected function casts(): array
    {
        return [
            'is_class_teacher' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
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
}
