<?php

namespace App\Support;

use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\TimetableEntry;
use Illuminate\Support\Collection;

class TimetableSampleSetup
{
    /**
     * @return array{targets:int, entries:int}
     */
    public static function createForSchool(School $school): array
    {
        return self::create(
            $school,
            SchoolClass::query()
                ->where('school_id', $school->getKey())
                ->where('is_active', true)
                ->orderBy('level')
                ->orderBy('name')
                ->get(),
        );
    }

    /**
     * @return array{targets:int, entries:int}
     */
    public static function createForTeacher(School $school): array
    {
        $classIds = TeacherWorkspace::formAssignments()
            ->pluck('school_class_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return self::create(
            $school,
            SchoolClass::query()
                ->where('school_id', $school->getKey())
                ->whereKey($classIds ?: [0])
                ->where('is_active', true)
                ->orderBy('level')
                ->orderBy('name')
                ->get(),
        );
    }

    /**
     * @param  Collection<int, SchoolClass>  $classes
     * @return array{targets:int, entries:int}
     */
    protected static function create(School $school, Collection $classes): array
    {
        $targets = self::targets($school, $classes);
        $entries = 0;

        foreach ($targets as $target) {
            $entries += self::createEntriesForTarget(
                school: $school,
                classId: $target['school_class_id'],
                sectionId: $target['class_section_id'],
            );
        }

        return [
            'targets' => count($targets),
            'entries' => $entries,
        ];
    }

    /**
     * @param  Collection<int, SchoolClass>  $classes
     * @return array<int, array{school_class_id:int, class_section_id:int|null}>
     */
    protected static function targets(School $school, Collection $classes): array
    {
        $assignedSections = TeacherWorkspace::formAssignments()
            ->map(fn ($assignment): array => [
                'school_class_id' => $assignment->school_class_id,
                'class_section_id' => $assignment->class_section_id,
            ])
            ->unique(fn (array $target): string => implode(':', [
                $target['school_class_id'],
                $target['class_section_id'] ?: 'all',
            ]))
            ->values();

        if (TeacherWorkspace::isTeacher()) {
            return $assignedSections->all();
        }

        return $classes
            ->flatMap(function (SchoolClass $class) use ($school): Collection {
                $sections = ClassSection::query()
                    ->where('school_id', $school->getKey())
                    ->where('school_class_id', $class->getKey())
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();

                if ($sections->isEmpty()) {
                    return collect([[
                        'school_class_id' => $class->getKey(),
                        'class_section_id' => null,
                    ]]);
                }

                return $sections->map(fn (ClassSection $section): array => [
                    'school_class_id' => $class->getKey(),
                    'class_section_id' => $section->getKey(),
                ]);
            })
            ->values()
            ->all();
    }

    protected static function createEntriesForTarget(School $school, int $classId, ?int $sectionId): int
    {
        $subjects = ClassSubject::query()
            ->with('subject')
            ->where('school_id', $school->getKey())
            ->where('school_class_id', $classId)
            ->where('is_active', true)
            ->orderByDesc('is_compulsory')
            ->orderBy('subject_id')
            ->get();

        $subjectPool = self::subjectPool($subjects);
        $subjectIndex = 0;
        $entries = 0;

        foreach (self::template() as $slot) {
            $attributes = [
                'school_id' => $school->getKey(),
                'school_class_id' => $classId,
                'class_section_id' => $sectionId,
                'day_of_week' => $slot['day_of_week'],
                'period_number' => $slot['period_number'],
            ];

            $values = [
                'starts_at' => $slot['starts_at'],
                'ends_at' => $slot['ends_at'],
                'entry_type' => $slot['entry_type'],
                'label' => $slot['label'],
                'subject_id' => null,
                'staff_id' => null,
            ];

            if ($slot['entry_type'] === TimetableEntry::TYPE_LESSON) {
                $subject = $subjectPool[$subjectIndex % max(count($subjectPool), 1)] ?? null;

                $values['subject_id'] = $subject?->subject_id;
                $values['staff_id'] = $subject?->staff_id;
                $values['label'] = $subject?->subject?->name ? null : 'Class lesson';

                $subjectIndex++;
            }

            TimetableEntry::query()->updateOrCreate($attributes, $values);
            $entries++;
        }

        return $entries;
    }

    /**
     * @param  Collection<int, ClassSubject>  $subjects
     * @return array<int, ClassSubject>
     */
    protected static function subjectPool(Collection $subjects): array
    {
        $pool = [];

        foreach ($subjects as $subject) {
            $repeat = max((int) ($subject->weekly_periods ?: 1), 1);

            for ($index = 0; $index < $repeat; $index++) {
                $pool[] = $subject;
            }
        }

        return $pool;
    }

    /**
     * @return array<int, array{day_of_week:int, period_number:int, starts_at:string, ends_at:string, entry_type:string, label:string|null}>
     */
    protected static function template(): array
    {
        $slots = [
            ['period_number' => 1, 'starts_at' => '08:00', 'ends_at' => '08:40', 'entry_type' => TimetableEntry::TYPE_LESSON, 'label' => null],
            ['period_number' => 2, 'starts_at' => '08:40', 'ends_at' => '09:20', 'entry_type' => TimetableEntry::TYPE_LESSON, 'label' => null],
            ['period_number' => 3, 'starts_at' => '09:20', 'ends_at' => '09:40', 'entry_type' => TimetableEntry::TYPE_BREAK, 'label' => 'Morning break'],
            ['period_number' => 4, 'starts_at' => '09:40', 'ends_at' => '10:20', 'entry_type' => TimetableEntry::TYPE_LESSON, 'label' => null],
            ['period_number' => 5, 'starts_at' => '10:20', 'ends_at' => '11:00', 'entry_type' => TimetableEntry::TYPE_LESSON, 'label' => null],
            ['period_number' => 6, 'starts_at' => '11:00', 'ends_at' => '11:30', 'entry_type' => TimetableEntry::TYPE_BREAK, 'label' => 'Lunch break'],
            ['period_number' => 7, 'starts_at' => '11:30', 'ends_at' => '12:10', 'entry_type' => TimetableEntry::TYPE_LESSON, 'label' => null],
            ['period_number' => 8, 'starts_at' => '12:10', 'ends_at' => '12:50', 'entry_type' => TimetableEntry::TYPE_LESSON, 'label' => null],
        ];

        $template = [];

        foreach (array_keys(TimetableEntry::DAYS) as $day) {
            foreach ($slots as $slot) {
                $template[] = [
                    'day_of_week' => $day,
                    ...$slot,
                ];
            }
        }

        return $template;
    }
}
