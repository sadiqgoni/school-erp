<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\GuardianStudent;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    public function getTitle(): string
    {
        return 'Edit Student Admission';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $student = $this->getRecord();

        $data['guardian_entries'] = $student->guardianLinks()
            ->with('guardian')
            ->get()
            ->map(fn (GuardianStudent $link): array => [
                'name' => $link->guardian?->name,
                'relationship' => $link->relationship,
                'phone' => $link->guardian?->phone,
                'alternate_phone' => $link->guardian?->alternate_phone,
                'email' => $link->guardian?->email,
                'occupation' => $link->guardian?->occupation,
                'address' => $link->guardian?->address,
                'receives_sms' => (int) $link->receives_sms,
                'can_pick_up' => (int) $link->can_pick_up,
                'is_primary_contact' => (int) $link->is_primary_contact,
                'notes' => $link->notes,
            ])
            ->all();

        $data['enrollment_entries'] = $student->enrollments()
            ->orderByDesc('enrolled_on')
            ->get()
            ->map(fn (Enrollment $enrollment): array => [
                'academic_year_id' => $enrollment->academic_year_id,
                'term_id' => $enrollment->term_id,
                'school_class_id' => $enrollment->school_class_id,
                'class_section_id' => $enrollment->class_section_id,
                'enrolled_on' => optional($enrollment->enrolled_on)->format('Y-m-d'),
                'status' => $enrollment->status,
                'remarks' => $enrollment->remarks,
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $guardianEntries = $data['guardian_entries'] ?? [];
            $enrollmentEntries = $data['enrollment_entries'] ?? [];

            unset($data['guardian_entries'], $data['enrollment_entries']);

            $record->update($data);

            $this->syncGuardians($record, $guardianEntries);
            $this->syncEnrollments($record, $enrollmentEntries);

            return $record;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $guardianEntries
     */
    protected function syncGuardians(Model $student, array $guardianEntries): void
    {
        $existingLinkIds = [];

        foreach ($guardianEntries as $index => $guardianEntry) {
            if (blank($guardianEntry['name'] ?? null) || blank($guardianEntry['phone'] ?? null)) {
                continue;
            }

            $guardian = Guardian::query()->updateOrCreate(
                [
                    'school_id' => $student->school_id,
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

            $link = GuardianStudent::query()->updateOrCreate(
                [
                    'school_id' => $student->school_id,
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

            $existingLinkIds[] = $link->getKey();
        }

        $student->guardianLinks()->whereNotIn('id', $existingLinkIds)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $enrollmentEntries
     */
    protected function syncEnrollments(Model $student, array $enrollmentEntries): void
    {
        $existingEnrollmentIds = [];

        foreach ($enrollmentEntries as $enrollmentEntry) {
            if (blank($enrollmentEntry['academic_year_id'] ?? null) || blank($enrollmentEntry['school_class_id'] ?? null)) {
                continue;
            }

            $enrollment = Enrollment::query()->updateOrCreate(
                [
                    'school_id' => $student->school_id,
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

            $existingEnrollmentIds[] = $enrollment->getKey();
        }

        $student->enrollments()->whereNotIn('id', $existingEnrollmentIds)->delete();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
