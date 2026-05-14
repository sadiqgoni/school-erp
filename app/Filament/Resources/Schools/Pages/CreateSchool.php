<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Resources\Schools\SchoolResource;
use App\Models\School;
use App\Models\User;
use App\Support\SchoolDivisionProvisioner;
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
            $sections = Arr::get($data, 'sections', array_keys(School::DIVISIONS));

            unset($data['admin_name'], $data['admin_email'], $data['admin_password'], $data['sections']);

            $school = static::getModel()::create($data);

            $schoolAdmin = User::query()->create([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'is_platform_admin' => false,
                'is_active' => true,
            ]);

            $divisionSchools = SchoolDivisionProvisioner::provision($school, $sections);

            $divisionSchools->each(function (School $divisionSchool, int $index) use ($schoolAdmin): void {
                $schoolAdmin->schools()->attach($divisionSchool, [
                    'role' => 'school_admin',
                    'is_primary' => $index === 0,
                ]);
            });

            $this->schoolAdminCredentials = [
                'email' => $adminEmail,
                'password' => $adminPassword,
                'portal' => url('/portal/'.$divisionSchools->first()->slug),
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
