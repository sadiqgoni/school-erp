<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Models\Enrollment;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditEnrollment extends EditRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['term_ids'] = filled($data['term_id'] ?? null) ? [$data['term_id']] : [];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $termIds = collect($data['term_ids'] ?? [null])
                ->filter(fn ($termId): bool => filled($termId))
                ->values();

            if ($termIds->isEmpty()) {
                $termIds = collect([null]);
            }

            unset($data['term_ids']);
            $data['school_id'] ??= $record->school_id ?? Filament::getTenant()?->getKey();

            $firstRecord = null;

            foreach ($termIds as $termId) {
                $updated = Enrollment::query()->updateOrCreate(
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

                $firstRecord ??= $updated;
            }

            return $firstRecord ?? $record;
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
