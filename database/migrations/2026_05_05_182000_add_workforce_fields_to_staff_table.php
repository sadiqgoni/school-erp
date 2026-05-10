<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table): void {
            $table->string('staff_type', 30)->default('teaching')->after('department_id');
            $table->string('highest_qualification')->nullable()->after('job_title');
            $table->string('trcn_number', 50)->nullable()->after('highest_qualification');

            $table->index(['school_id', 'staff_type']);
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table): void {
            $table->dropIndex(['school_id', 'staff_type']);
            $table->dropColumn(['staff_type', 'highest_qualification', 'trcn_number']);
        });
    }
};
