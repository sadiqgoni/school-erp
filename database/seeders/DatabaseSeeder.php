<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('password'),
                'is_platform_admin' => true,
                'is_active' => true,
            ],
        );

        $school = School::query()->updateOrCreate(
            ['code' => 'DEMO'],
            [
                'name' => 'Demo International School',
                'slug' => 'demo-international-school',
                'email' => 'school@example.com',
                'phone' => '+2348000000000',
                'address' => 'School address goes here',
                'city' => 'Maiduguri',
                'state' => 'Borno',
                'country' => 'Nigeria',
                'primary_color' => '#0f766e',
                'subscription_plan' => 'trial',
                'student_limit' => 1000,
                'enabled_modules' => [
                    'students',
                    'staff',
                    'attendance',
                    'fees',
                    'exams',
                    'library',
                    'communications',
                ],
                'is_active' => true,
            ],
        );

        $admin->schools()->syncWithoutDetaching([
            $school->id => [
                'role' => 'platform_admin',
                'is_primary' => true,
            ],
        ]);
    }
}
