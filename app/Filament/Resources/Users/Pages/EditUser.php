<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $schoolRole = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (Filament::getCurrentPanel()?->getId() === 'school') {
            $data['school_role'] = $this->record->roleForSchool(Filament::getTenant()) ?? 'staff';
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->schoolRole = $data['school_role'] ?? null;
        unset($data['school_role']);

        if (Filament::getCurrentPanel()?->getId() === 'school') {
            unset($data['is_platform_admin']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $tenant = Filament::getTenant();

        if (Filament::getCurrentPanel()?->getId() === 'school' && $tenant && $this->schoolRole) {
            $this->record->schools()->updateExistingPivot($tenant->getKey(), [
                'role' => $this->schoolRole,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
