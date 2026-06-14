<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 40)
                    ->default('user')
                    ->after('password')
                    ->index();
            }
        });

        DB::table('users')
            ->where('is_platform_admin', true)
            ->update(['role' => 'superadmin']);

        DB::table('school_user')
            ->where('role', 'school_admin')
            ->update(['role' => 'admin']);

        DB::table('school_user')
            ->where('role', 'platform_admin')
            ->update(['role' => 'superadmin']);
    }

    public function down(): void
    {
        DB::table('school_user')
            ->where('role', 'admin')
            ->update(['role' => 'school_admin']);

        DB::table('school_user')
            ->where('role', 'superadmin')
            ->update(['role' => 'platform_admin']);

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropIndex(['role']);
                $table->dropColumn('role');
            }
        });
    }
};
