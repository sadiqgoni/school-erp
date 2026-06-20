<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table): void {
            if (! Schema::hasColumn('staff', 'salary_grade_level')) {
                $table->string('salary_grade_level', 50)->nullable()->after('salary_template_id');
            }

            if (! Schema::hasColumn('staff', 'salary_step')) {
                $table->string('salary_step', 50)->nullable()->after('salary_grade_level');
            }
        });

        Schema::create('salary_allowances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_template_id')->nullable()->constrained('salary_templates')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('grade_level', 50)->nullable();
            $table->string('step', 50)->nullable();
            $table->string('calculation_type', 30)->default('fixed');
            $table->decimal('value', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'code']);
            $table->index(['school_id', 'grade_level', 'step']);
        });

        Schema::create('salary_deductions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_template_id')->nullable()->constrained('salary_templates')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('grade_level', 50)->nullable();
            $table->string('step', 50)->nullable();
            $table->string('calculation_type', 30)->default('fixed');
            $table->decimal('value', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'code']);
            $table->index(['school_id', 'grade_level', 'step']);
        });

        Schema::create('salary_postings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('salary_template_id')->nullable()->constrained('salary_templates')->nullOnDelete();
            $table->date('payroll_month');
            $table->string('reference', 80);
            $table->string('staff_number', 50)->nullable();
            $table->string('staff_name');
            $table->string('grade_level', 50)->nullable();
            $table->string('step', 50)->nullable();
            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->decimal('allowances_total', 14, 2)->default(0);
            $table->decimal('gross_pay', 14, 2)->default(0);
            $table->decimal('deductions_total', 14, 2)->default(0);
            $table->decimal('net_pay', 14, 2)->default(0);
            $table->json('allowance_breakdown')->nullable();
            $table->json('deduction_breakdown')->nullable();
            $table->string('status', 30)->default('posted');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'staff_id', 'payroll_month']);
            $table->unique(['school_id', 'reference']);
            $table->index(['school_id', 'payroll_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_postings');
        Schema::dropIfExists('salary_deductions');
        Schema::dropIfExists('salary_allowances');

        Schema::table('staff', function (Blueprint $table): void {
            if (Schema::hasColumn('staff', 'salary_step')) {
                $table->dropColumn('salary_step');
            }

            if (Schema::hasColumn('staff', 'salary_grade_level')) {
                $table->dropColumn('salary_grade_level');
            }
        });
    }
};
