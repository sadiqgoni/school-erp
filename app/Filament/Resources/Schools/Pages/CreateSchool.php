<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Resources\Schools\SchoolResource;
use App\Mail\SchoolAdminWelcomeMail;
use App\Models\School;
use App\Models\User;
use App\Support\SchoolDivisionProvisioner;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Throwable;

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
                    'role' => User::SCHOOL_ROLE_ADMIN,
                    'is_primary' => $index === 0,
                ]);
            });

            $this->schoolAdminCredentials = [
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => $adminPassword,
                'portal' => url('/portal/'.$divisionSchools->first()->slug),
            ];

            return $school;
        });
    }

    protected function afterCreate(): void
    {
        $emailSent = $this->sendSchoolAdminWelcomeEmail();

        Notification::make()
            ->success()
            ->persistent()
            ->title('School created successfully')
            ->body($emailSent
                ? "The school admin login details have been sent to {$this->schoolAdminCredentials['email']}."
                : "Portal: {$this->schoolAdminCredentials['portal']}\nLogin: {$this->schoolAdminCredentials['email']}\nPassword: {$this->schoolAdminCredentials['password']}\n\nEmail could not be sent. Check mail settings and resend manually.")
            ->send();
    }

    protected function sendSchoolAdminWelcomeEmail(): bool
    {
        try {
            Mail::to($this->schoolAdminCredentials['email'])
                ->send(new SchoolAdminWelcomeMail(
                    school: $this->record,
                    adminName: $this->schoolAdminCredentials['name'],
                    email: $this->schoolAdminCredentials['email'],
                    temporaryPassword: $this->schoolAdminCredentials['password'],
                    portalUrl: $this->schoolAdminCredentials['portal'],
                ));

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
