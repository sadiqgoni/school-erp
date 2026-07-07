<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentPromotion
{
    public const OUTCOME_PROMOTED = 'promoted';

    public const OUTCOME_REPEATED = 'repeated';

    public const OUTCOME_GRADUATED = 'graduated';

    public const OUTCOMES = [
        self::OUTCOME_PROMOTED => 'Promote to another class',
        self::OUTCOME_REPEATED => 'Repeat (same class next session)',
        self::OUTCOME_GRADUATED => 'Graduate (finished school)',
    ];

    /**
     * Apply an outcome to a set of enrollments and return ['moved' => int, 'skipped' => int].
     *
     * @param  Collection<int, Enrollment>  $enrollments
     * @param  array{outcome: string, target_academic_year_id?: int|null, target_term_id?: int|null, target_class_id?: int|null, target_section_id?: int|null}  $data
     */
    public static function apply(Collection $enrollments, array $data): array
    {
        $moved = 0;
        $skipped = 0;

        foreach ($enrollments as $enrollment) {
            $done = DB::transaction(function () use ($enrollment, $data): bool {
                return match ($data['outcome']) {
                    self::OUTCOME_GRADUATED => self::graduate($enrollment),
                    self::OUTCOME_REPEATED => self::moveForward($enrollment, $data, keepClass: true),
                    default => self::moveForward($enrollment, $data, keepClass: false),
                };
            });

            $done ? $moved++ : $skipped++;
        }

        return ['moved' => $moved, 'skipped' => $skipped];
    }

    protected static function graduate(Enrollment $enrollment): bool
    {
        if ($enrollment->status !== 'active') {
            return false;
        }

        $enrollment->update(['status' => self::OUTCOME_GRADUATED]);

        Student::query()
            ->whereKey($enrollment->student_id)
            ->update(['status' => 'graduated']);

        return true;
    }

    protected static function moveForward(Enrollment $enrollment, array $data, bool $keepClass): bool
    {
        if ($enrollment->status !== 'active') {
            return false;
        }

        $targetClassId = $keepClass
            ? ($data['target_class_id'] ?? $enrollment->school_class_id)
            : $data['target_class_id'];

        $targetYearId = $data['target_academic_year_id'] ?? null;

        if (! $targetClassId || ! $targetYearId) {
            return false;
        }

        $alreadyPlaced = Enrollment::query()
            ->where('student_id', $enrollment->student_id)
            ->where('academic_year_id', $targetYearId)
            ->where(function ($query) use ($data): void {
                $termId = $data['target_term_id'] ?? null;
                $termId
                    ? $query->where('term_id', $termId)
                    : $query->whereNull('term_id');
            })
            ->exists();

        if ($alreadyPlaced) {
            return false;
        }

        $enrollment->update([
            'status' => $keepClass ? self::OUTCOME_REPEATED : self::OUTCOME_PROMOTED,
        ]);

        Enrollment::query()->create([
            'school_id' => $enrollment->school_id,
            'student_id' => $enrollment->student_id,
            'academic_year_id' => $targetYearId,
            'term_id' => $data['target_term_id'] ?? null,
            'school_class_id' => $targetClassId,
            'class_section_id' => $keepClass
                ? ($data['target_section_id'] ?? $enrollment->class_section_id)
                : ($data['target_section_id'] ?? null),
            'enrolled_on' => today(),
            'status' => 'active',
            'remarks' => sprintf(
                '%s from %s on %s',
                $keepClass ? 'Repeated' : 'Promoted',
                $enrollment->schoolClass?->name ?? 'previous class',
                today()->format('d M Y'),
            ),
        ]);

        return true;
    }
}
