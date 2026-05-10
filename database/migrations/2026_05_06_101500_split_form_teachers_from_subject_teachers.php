<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->string('assignment_role', 40)->default('form_teacher')->after('class_section_id');
            $table->foreignId('subject_id')->nullable()->change();
        });

        DB::table('teaching_assignments')
            ->whereNotNull('subject_id')
            ->update(['assignment_role' => 'subject_teacher']);

        Schema::table('class_subjects', function (Blueprint $table): void {
            $table->foreignId('staff_id')->nullable()->after('subject_id')->constrained('staff')->nullOnDelete();
            $table->index(['school_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::table('class_subjects', function (Blueprint $table): void {
            $table->dropIndex(['school_id', 'staff_id']);
            $table->dropConstrainedForeignId('staff_id');
        });

        DB::table('teaching_assignments')
            ->where('assignment_role', 'form_teacher')
            ->update(['subject_id' => DB::raw('(select id from subjects limit 1)')]);

        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->dropColumn('assignment_role');
            $table->foreignId('subject_id')->nullable(false)->change();
        });
    }
};
