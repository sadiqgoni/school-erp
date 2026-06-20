<?php

namespace App\Filament\Resources\TeachingAssignments\Pages;

use App\Filament\Resources\TeachingAssignments\TeachingAssignmentResource;
use App\Models\TeachingAssignment;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateTeachingAssignment extends CreateRecord
{
    protected static string $resource = TeachingAssignmentResource::class;

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
                $record = TeachingAssignment::query()->updateOrCreate(
                    [
                        'staff_id' => $data['staff_id'],
                        'academic_year_id' => $data['academic_year_id'],
                        'term_id' => $termId,
                        'school_class_id' => $data['school_class_id'],
                        'class_section_id' => $data['class_section_id'] ?: null,
                        'subject_id' => $data['subject_id'] ?? null,
                    ],
                    [
                        ...$data,
                        'term_id' => $termId,
                    ],
                );

                $firstRecord ??= $record;
            }

            return $firstRecord;
        });
    }
}
