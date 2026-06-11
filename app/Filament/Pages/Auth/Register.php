<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Register as BaseRegister;

class Register extends BaseRegister
{
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
            'is_platform_admin' => true,
        ];
    }
}
