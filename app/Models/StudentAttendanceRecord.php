<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'student_attendance_id', 'student_id', 'status', 'arrival_time', 'remarks'])]
class StudentAttendanceRecord extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['arrival_time' => 'datetime:H:i'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentAttendance(): BelongsTo
    {
        return $this->belongsTo(StudentAttendance::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
