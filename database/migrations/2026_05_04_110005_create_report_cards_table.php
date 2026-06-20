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
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_score', 8, 2)->default(0);
            $table->decimal('average_score', 5, 2)->default(0);
            $table->unsignedSmallInteger('position')->nullable();
            $table->unsignedSmallInteger('attendance_total_days')->default(0);
            $table->unsignedSmallInteger('attendance_present_days')->default(0);
            $table->unsignedSmallInteger('attendance_absent_days')->default(0);
            $table->text('teacher_comment')->nullable();
            $table->text('principal_comment')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
            $table->index(['school_id', 'academic_year_id', 'term_id', 'status'], 'report_school_year_term_status_idx');
        });

        Schema::create('result_trait_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 30)->default('affective');
            $table->unsignedTinyInteger('max_rating')->default(5);
            $table->unsignedSmallInteger('position')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'category', 'name']);
            $table->index(['school_id', 'category', 'is_active']);
        });

        Schema::create('report_card_trait_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_trait_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->unique(['report_card_id', 'result_trait_item_id'], 'report_trait_unique');
            $table->index(['school_id', 'result_trait_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_card_trait_ratings');
        Schema::dropIfExists('result_trait_items');
        Schema::dropIfExists('report_cards');
    }
};
