<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->string('grade_level', 50)->nullable();
            $table->string('step', 50)->nullable();
            $table->decimal('monthly_basic', 14, 2)->default(0);
            $table->decimal('annual_basic', 14, 2)->default(0);
            $table->decimal('housing_allowance', 14, 2)->default(0);
            $table->decimal('transport_allowance', 14, 2)->default(0);
            $table->decimal('meal_allowance', 14, 2)->default(0);
            $table->decimal('other_allowance', 14, 2)->default(0);
            $table->decimal('pension_deduction', 14, 2)->default(0);
            $table->decimal('tax_deduction', 14, 2)->default(0);
            $table->decimal('other_deduction', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'code']);
            $table->index(['school_id', 'grade_level', 'step'], 'salary_template_school_grade_step_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_templates');
    }
};
