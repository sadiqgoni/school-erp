<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Models\Enrollment;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateEnrollment extends CreateRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $termIds = collect($data['term_ids'] ?? [null])
                ->filter(fn ($termId): bool => filled($termId))
                ->values();

            if ($termIds->isEmpty()) {
                $termIds = collect([null]);
            }

            unset($data['term_ids']);
            $data['school_id'] ??= Filament::getTenant()?->getKey();

            $firstRecord = null;

            foreach ($termIds as $termId) {
                $record = Enrollment::query()->updateOrCreate(
                    [
                        'school_id' => $data['school_id'],
                        'student_id' => $data['student_id'],
                        'academic_year_id' => $data['academic_year_id'],
                        'term_id' => $termId,
                    ],
                    [
                        ...$data,
                        'term_id' => $termId,
                        'class_section_id' => $data['class_section_id'] ?: null,
                    ],
                );

                $firstRecord ??= $record;
            }

            return $firstRecord;
        });
    }
}
