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
        Schema::create('student_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('present');
            $table->time('arrival_time')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['student_attendance_id', 'student_id'], 'stud_att_rec_att_student_unique');
            $table->index(['school_id', 'student_id', 'status'], 'stud_att_rec_school_student_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendance_records');
    }
};
