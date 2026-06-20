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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('staff_type', 30)->default('teaching');
            $table->string('staff_number', 50);
            $table->string('first_name', 80);
            $table->string('middle_name', 80)->nullable();
            $table->string('last_name', 80);
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Nigeria');
            $table->string('employment_type', 40)->default('full_time');
            $table->string('job_title')->nullable();
            $table->string('highest_qualification')->nullable();
            $table->string('course_specialization')->nullable();
            $table->string('education_school')->nullable();
            $table->string('trcn_number', 50)->nullable();
            $table->date('hire_date')->nullable();
            $table->decimal('basic_salary', 12, 2)->nullable();
            $table->foreignId('salary_template_id')->nullable()->constrained('salary_templates')->nullOnDelete();
            $table->string('salary_grade_level', 50)->nullable();
            $table->string('salary_step', 50)->nullable();
            $table->foreignId('staff_bank_id')->nullable()->constrained('staff_banks')->nullOnDelete();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number', 30)->nullable();
            $table->string('status', 30)->default('active');
            $table->string('photo_path')->nullable();
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_relation', 50)->nullable();
            $table->string('next_of_kin_phone', 30)->nullable();
            $table->string('next_of_kin_occupation')->nullable();
            $table->text('next_of_kin_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'staff_number']);
            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'staff_type']);
            $table->index(['school_id', 'last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
