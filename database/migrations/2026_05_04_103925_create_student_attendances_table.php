<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_section_id')->nullable()->constrained()->nullOnDelete();
            $table->date('attendance_date');
            $table->string('session', 30)->default('morning');
            $table->foreignId('taken_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status', 30)->default('draft');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'school_class_id', 'class_section_id', 'attendance_date', 'session'], 'student_attendance_unique');
            $table->index(['school_id', 'attendance_date', 'status'], 'student_att_school_date_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
