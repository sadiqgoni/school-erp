<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'school_id',
    'academic_year_id',
    'term_id',
    'school_class_id',
    'class_section_id',
    'attendance_date',
    'session',
    'taken_by_id',
    'status',
    'remarks',
])]
class StudentAttendance extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['attendance_date' => 'date'];
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

    public function takenBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'taken_by_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(StudentAttendanceRecord::class);
    }
}
