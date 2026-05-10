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
        Schema::create('compiled_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_score', 6, 2);
            $table->string('grade', 10)->nullable();
            $table->decimal('grade_point', 4, 2)->nullable();
            $table->string('remark')->nullable();
            $table->unsignedSmallInteger('position')->nullable();
            $table->string('status', 30)->default('compiled');
            $table->timestamps();

            $table->unique(['exam_id', 'student_id', 'subject_id'], 'compiled_result_exam_student_subject_unique');
            $table->index(['school_id', 'exam_id', 'student_id'], 'compiled_result_school_exam_student_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compiled_results');
    }
};
