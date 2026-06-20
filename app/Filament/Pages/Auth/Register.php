<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    public function mount(): void
    {
        abort_if(Filament::getCurrentPanel()?->getId() !== 'admin', 404);
        abort_if(User::query()->exists(), 404);

        parent::mount();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        return [
            ...$data,
            'email_verified_at' => now(),
            'is_active' => true,
            'role' => User::ROLE_SUPERADMIN,
            'is_platform_admin' => true,
        ];
    }

    protected function handleRegistration(array $data): Model
    {
        abort_if(User::query()->exists(), 409);

        return parent::handleRegistration($data);
    }
}
