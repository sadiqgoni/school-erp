<?php

namespace Database\Seeders;

use App\Models\Guardian;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ParentAccountsSeeder extends Seeder
{
    public function run(): void
    {
        Guardian::query()
            ->whereNotNull('email')
            ->where('is_active', true)
            ->get()
            ->each(function (Guardian $guardian): void {
                $user = User::query()->firstOrCreate(
                    ['email' => $guardian->email],
                    [
                        'name' => $guardian->name,
                        'password' => Hash::make('password'),
                        'is_platform_admin' => false,
                        'is_active' => true,
                    ],
                );

                $guardian->forceFill(['user_id' => $user->getKey()])->save();

                $user->schools()->syncWithoutDetaching([
                    $guardian->school_id => [
                        'role' => 'parent',
                        'is_primary' => false,
                    ],
                ]);
            });
    }
}
