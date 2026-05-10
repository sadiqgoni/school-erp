<?php

namespace App\Filament\Resources\Guardians\Pages;

use App\Filament\Resources\Guardians\GuardianResource;
use App\Models\GuardianStudent;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateGuardian extends CreateRecord
{
    protected static string $resource = GuardianResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $studentLinks = $data['student_links'] ?? [];

            unset($data['student_links']);

            $data['school_id'] ??= Filament::getTenant()?->getKey();

            $guardian = static::getModel()::query()->create($data);

            $this->syncStudentLinks($guardian, $studentLinks);

            return $guardian;
        });
    }

    protected function syncStudentLinks(Model $guardian, array $studentLinks): void
    {
        foreach ($studentLinks as $index => $link) {
            if (blank($link['student_id'] ?? null)) {
                continue;
            }

            GuardianStudent::query()->updateOrCreate(
                [
                    'school_id' => $guardian->school_id,
                    'guardian_id' => $guardian->getKey(),
                    'student_id' => $link['student_id'],
                ],
                [
                    'relationship' => $link['relationship'] ?? 'guardian',
                    'is_primary_contact' => (bool) (($link['is_primary_contact'] ?? false) || $index === 0),
                    'can_pick_up' => (bool) ($link['can_pick_up'] ?? true),
                    'receives_sms' => (bool) ($link['receives_sms'] ?? true),
                    'notes' => $link['notes'] ?? null,
                ],
            );
        }
    }
}
