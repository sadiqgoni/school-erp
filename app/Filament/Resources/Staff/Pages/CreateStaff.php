<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use App\Models\Staff;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

                Notification::make()
                    ->title('Staff login account created')
                    ->body("{$staff->full_name} can now sign in with {$email}.")
                    ->success()
                    ->send();
            }

            return $staff;
        });
    }
}
