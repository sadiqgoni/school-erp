<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\DB;

class StudentClassPlacement
{
    public function placeStudents(SchoolClass $schoolClass, array $data): int
    {
        $saved = 0;

        DB::transaction(function () use ($schoolClass, $data, &$saved): void {
            foreach ($data['student_ids'] ?? [] as $studentId) {
                if (blank($studentId)) {
                    continue;
                }

                Enrollment::query()->updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'academic_year_id' => $data['academic_year_id'],
                    ],
                    [
                        'school_id' => $schoolClass->school_id,
                        'term_id' => $data['term_id'] ?? null,
                        'school_class_id' => $schoolClass->getKey(),
                        'class_section_id' => $data['class_section_id'] ?? null,
                        'enrolled_on' => $data['enrolled_on'] ?? today()->toDateString(),
                        'status' => $data['status'] ?? 'active',
                        'remarks' => $data['remarks'] ?? null,
                    ],
                );

                $saved++;
            }
        });

        return $saved;
    }
}
