<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\GuardianStudent;
use App\Models\Student;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    public function getTitle(): string
    {
        return 'Student Admission';
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $tenant = Filament::getTenant();
            $guardianEntries = $data['guardian_entries'] ?? [];
            $enrollmentEntries = $data['enrollment_entries'] ?? [];

            unset($data['guardian_entries'], $data['enrollment_entries']);

            $data['school_id'] ??= $tenant?->getKey();

            /** @var Student $student */
            $student = static::getModel()::query()->create($data);

            $this->syncGuardians($student, $guardianEntries);
            $this->syncEnrollments($student, $enrollmentEntries);

            return $student;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $guardianEntries
     */
    protected function syncGuardians(Model $student, array $guardianEntries): void
    {
        $tenant = Filament::getTenant();

        foreach ($guardianEntries as $index => $guardianEntry) {
            if (blank($guardianEntry['name'] ?? null) || blank($guardianEntry['phone'] ?? null)) {
                continue;
            }

            $guardian = Guardian::query()->updateOrCreate(
                [
                    'school_id' => $tenant?->getKey(),
                    'phone' => $guardianEntry['phone'],
                ],
                [
                    'name' => $guardianEntry['name'],
                    'alternate_phone' => $guardianEntry['alternate_phone'] ?: null,
                    'email' => $guardianEntry['email'] ?: null,
                    'occupation' => $guardianEntry['occupation'] ?: null,
                    'address' => $guardianEntry['address'] ?: null,
                    'is_active' => true,
                ],
            );

            GuardianStudent::query()->updateOrCreate(
                [
                    'school_id' => $tenant?->getKey(),
                    'guardian_id' => $guardian->getKey(),
                    'student_id' => $student->getKey(),
                ],
                [
                    'relationship' => $guardianEntry['relationship'] ?? 'guardian',
                    'is_primary_contact' => (bool) (($guardianEntry['is_primary_contact'] ?? 0) || $index === 0),
                    'can_pick_up' => (bool) ($guardianEntry['can_pick_up'] ?? true),
                    'receives_sms' => (bool) ($guardianEntry['receives_sms'] ?? true),
                    'notes' => $guardianEntry['notes'] ?: null,
                ],
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $enrollmentEntries
     */
    protected function syncEnrollments(Model $student, array $enrollmentEntries): void
    {
        $tenant = Filament::getTenant();

        foreach ($enrollmentEntries as $enrollmentEntry) {
            if (blank($enrollmentEntry['academic_year_id'] ?? null) || blank($enrollmentEntry['school_class_id'] ?? null)) {
                continue;
            }

            Enrollment::query()->updateOrCreate(
                [
                    'school_id' => $tenant?->getKey(),
                    'student_id' => $student->getKey(),
                    'academic_year_id' => $enrollmentEntry['academic_year_id'],
                    'term_id' => $enrollmentEntry['term_id'] ?: null,
                    'school_class_id' => $enrollmentEntry['school_class_id'],
                    'class_section_id' => $enrollmentEntry['class_section_id'] ?: null,
                ],
                [
                    'enrolled_on' => $enrollmentEntry['enrolled_on'] ?: null,
                    'status' => $enrollmentEntry['status'] ?? 'active',
                    'remarks' => $enrollmentEntry['remarks'] ?: null,
                ],
            );
        }
    }
}
