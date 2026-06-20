<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('enrollments', 'enrollments_student_id_index')) {
            Schema::table('enrollments', function (Blueprint $table): void {
                $table->index('student_id', 'enrollments_student_id_index');
            });
        }

        if (Schema::hasIndex('enrollments', 'enrollments_student_id_academic_year_id_unique')) {
            Schema::table('enrollments', function (Blueprint $table): void {
                $table->dropUnique(['student_id', 'academic_year_id']);
            });
        }

        if (! Schema::hasIndex('enrollments', 'enrollment_student_year_term_unique')) {
            Schema::table('enrollments', function (Blueprint $table): void {
                $table->unique(['student_id', 'academic_year_id', 'term_id'], 'enrollment_student_year_term_unique');
            });
        }

        if (! Schema::hasIndex('teaching_assignments', 'teaching_assignments_staff_id_index')) {
            Schema::table('teaching_assignments', function (Blueprint $table): void {
                $table->index('staff_id', 'teaching_assignments_staff_id_index');
            });
        }

        if (Schema::hasIndex('teaching_assignments', 'teaching_assignment_unique')) {
            Schema::table('teaching_assignments', function (Blueprint $table): void {
                $table->dropUnique('teaching_assignment_unique');
            });
        }

        if (! Schema::hasIndex('teaching_assignments', 'teaching_assignment_unique')) {
            Schema::table('teaching_assignments', function (Blueprint $table): void {
                $table->unique(['staff_id', 'academic_year_id', 'term_id', 'school_class_id', 'class_section_id', 'subject_id'], 'teaching_assignment_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('teaching_assignments', 'teaching_assignment_unique')) {
            Schema::table('teaching_assignments', function (Blueprint $table): void {
                $table->dropUnique('teaching_assignment_unique');
            });
        }

        if (! Schema::hasIndex('teaching_assignments', 'teaching_assignment_unique')) {
            Schema::table('teaching_assignments', function (Blueprint $table): void {
                $table->unique(['staff_id', 'academic_year_id', 'school_class_id', 'class_section_id', 'subject_id'], 'teaching_assignment_unique');
            });
        }

        if (Schema::hasIndex('enrollments', 'enrollment_student_year_term_unique')) {
            Schema::table('enrollments', function (Blueprint $table): void {
                $table->dropUnique('enrollment_student_year_term_unique');
            });
        }

        if (! Schema::hasIndex('enrollments', 'enrollments_student_id_academic_year_id_unique')) {
            Schema::table('enrollments', function (Blueprint $table): void {
                $table->unique(['student_id', 'academic_year_id']);
            });
        }
    }
};
