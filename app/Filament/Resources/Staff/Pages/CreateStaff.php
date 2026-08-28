<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use App\Mail\LoginCredentialsMail;
use App\Models\School;
use App\Models\Staff;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    public function getTitle(): string
    {
        return 'Create Staff Profile';
    }

    protected function handleRecordCreation(array $data): Model
    {
        $createLogin = (bool) ($this->data['create_login_account'] ?? false);
        $loginEmail = $this->data['login_email'] ?? null;
        $temporaryPassword = $this->data['temporary_password'] ?? null;

        return DB::transaction(function () use ($data, $createLogin, $loginEmail, $temporaryPassword): Model {
            /** @var Staff $staff */
            $staff = static::getModel()::query()->create($data);

            if ($createLogin) {
                $email = $loginEmail ?: $staff->email;

                $user = User::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $staff->full_name,
                        'password' => Hash::make($temporaryPassword),
                        'is_platform_admin' => false,
                        'is_active' => true,
                        'must_change_password' => true,
                    ],
                );

                $schoolId = $staff->school_id ?: Filament::getTenant()?->getKey();

                if ($schoolId) {
                    $user->schools()->syncWithoutDetaching([
                        $schoolId => [
                            'role' => $staff->staff_type === Staff::TYPE_TEACHING ? 'teacher' : 'staff',
                            'is_primary' => false,
                        ],
                    ]);
                }

                $staff->forceFill([
                    'user_id' => $user->getKey(),
                    'email' => $staff->email ?: $email,
                ])->save();

                $emailSent = $this->sendLoginCredentials($staff, $email, $temporaryPassword);

                Notification::make()
                    ->title('Staff login account created')
                    ->body($emailSent
                        ? "{$staff->full_name} can now sign in with {$email}. Login details have been emailed."
                        : "{$staff->full_name} can now sign in with {$email}. Email could not be sent, so share the temporary password manually.")
                    ->success()
                    ->send();
            }

            return $staff;
        });
    }

    protected function sendLoginCredentials(Staff $staff, string $email, ?string $temporaryPassword): bool
    {
        if (blank($temporaryPassword)) {
            return false;
        }

        $school = School::query()->find($staff->school_id ?: Filament::getTenant()?->getKey());

        try {
            Mail::to($email)->send(new LoginCredentialsMail(
                school: $school,
                name: $staff->full_name,
                email: $email,
                temporaryPassword: $temporaryPassword,
                portalUrl: $school?->portalUrl() ?? url('/'),
                roleLabel: $staff->staff_type === Staff::TYPE_TEACHING ? 'teacher portal' : 'staff portal',
            ));

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
