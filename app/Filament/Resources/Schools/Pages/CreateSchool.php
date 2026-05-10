<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Resources\Schools\SchoolResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    protected array $schoolAdminCredentials = [];

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $adminName = Arr::get($data, 'admin_name');
            $adminEmail = Arr::get($data, 'admin_email');
            $adminPassword = Arr::get($data, 'admin_password');

            unset($data['admin_name'], $data['admin_email'], $data['admin_password']);

            $school = static::getModel()::create($data);

            $schoolAdmin = User::query()->create([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'is_platform_admin' => false,
                'is_active' => true,
            ]);

            $schoolAdmin->schools()->attach($school, [
                'role' => 'school_admin',
                'is_primary' => true,
            ]);

            $this->schoolAdminCredentials = [
                'email' => $adminEmail,
                'password' => $adminPassword,
                'portal' => url("/portal/{$school->slug}"),
            ];

            return $school;
        });
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->persistent()
            ->title('School created successfully')
            ->body("Portal: {$this->schoolAdminCredentials['portal']}\nLogin: {$this->schoolAdminCredentials['email']}\nPassword: {$this->schoolAdminCredentials['password']}")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
