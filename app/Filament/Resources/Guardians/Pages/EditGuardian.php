<?php

namespace App\Filament\Resources\Guardians\Pages;

use App\Filament\Resources\Guardians\GuardianResource;
use App\Models\GuardianStudent;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditGuardian extends EditRecord
{
    protected static string $resource = GuardianResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['student_links'] = $this->getRecord()->studentLinks()
            ->get()
            ->map(fn (GuardianStudent $link): array => [
                'student_id' => $link->student_id,
                'relationship' => $link->relationship,
                'is_primary_contact' => $link->is_primary_contact,
                'can_pick_up' => $link->can_pick_up,
                'receives_sms' => $link->receives_sms,
                'notes' => $link->notes,
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $studentLinks = $data['student_links'] ?? [];

            unset($data['student_links']);

            $record->update($data);

            $existingIds = [];

            foreach ($studentLinks as $index => $link) {
                if (blank($link['student_id'] ?? null)) {
                    continue;
                }

                $recordLink = GuardianStudent::query()->updateOrCreate(
                    [
                        'school_id' => $record->school_id,
                        'guardian_id' => $record->getKey(),
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

                $existingIds[] = $recordLink->getKey();
            }

            $record->studentLinks()->whereNotIn('id', $existingIds)->delete();

            return $record;
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
