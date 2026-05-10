<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table): void {
            $table->string('course_specialization')->nullable()->after('highest_qualification');
            $table->string('education_school')->nullable()->after('course_specialization');
            $table->string('next_of_kin_name')->nullable()->after('photo_path');
            $table->string('next_of_kin_relation', 50)->nullable()->after('next_of_kin_name');
            $table->string('next_of_kin_phone', 30)->nullable()->after('next_of_kin_relation');
            $table->string('next_of_kin_occupation')->nullable()->after('next_of_kin_phone');
            $table->text('next_of_kin_address')->nullable()->after('next_of_kin_occupation');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table): void {
            $table->dropColumn([
                'course_specialization',
                'education_school',
                'next_of_kin_name',
                'next_of_kin_relation',
                'next_of_kin_phone',
                'next_of_kin_occupation',
                'next_of_kin_address',
            ]);
        });
    }
};
