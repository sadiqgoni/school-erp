<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $schoolRole = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->schoolRole = $data['school_role'] ?? User::SCHOOL_ROLE_STAFF;
        unset($data['school_role']);

        if (Filament::getCurrentPanel()?->getId() === 'school') {
            $data['is_platform_admin'] = false;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation($data);
        $tenant = Filament::getTenant();

        if (Filament::getCurrentPanel()?->getId() === 'school' && $tenant) {
            $record->schools()->syncWithoutDetaching([
                $tenant->getKey() => [
                    'role' => $this->schoolRole ?: User::SCHOOL_ROLE_STAFF,
                    'is_primary' => false,
                ],
            ]);
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
