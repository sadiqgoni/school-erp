<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_item_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_template_id')->nullable()->constrained('salary_templates')->nullOnDelete();
            $table->foreignId('ledger_account_id')->nullable()->constrained('ledger_accounts')->nullOnDelete();
            $table->string('type', 20);
            $table->string('code', 50);
            $table->string('name');
            $table->string('grade_level', 50)->nullable();
            $table->string('step', 50)->nullable();
            $table->string('calculation_type', 30)->default('fixed');
            $table->decimal('value', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'type', 'code']);
            $table->index(['school_id', 'type', 'grade_level', 'step']);
        });

        if (Schema::hasTable('salary_allowances')) {
            DB::table('salary_allowances')
                ->orderBy('id')
                ->get()
                ->each(function ($item): void {
                    DB::table('payroll_item_types')->updateOrInsert(
                        [
                            'school_id' => $item->school_id,
                            'type' => 'allowance',
                            'code' => $item->code,
                        ],
                        [
                            'salary_template_id' => $item->salary_template_id,
                            'ledger_account_id' => $item->ledger_account_id ?? null,
                            'name' => $item->name,
                            'grade_level' => $item->grade_level,
                            'step' => $item->step,
                            'calculation_type' => $item->calculation_type,
                            'value' => $item->value,
                            'is_active' => $item->is_active,
                            'notes' => $item->notes,
                            'created_at' => $item->created_at,
                            'updated_at' => $item->updated_at,
                        ],
                    );
                });
        }

        if (Schema::hasTable('salary_deductions')) {
            DB::table('salary_deductions')
                ->orderBy('id')
                ->get()
                ->each(function ($item): void {
                    DB::table('payroll_item_types')->updateOrInsert(
                        [
                            'school_id' => $item->school_id,
                            'type' => 'deduction',
                            'code' => $item->code,
                        ],
                        [
                            'salary_template_id' => $item->salary_template_id,
                            'ledger_account_id' => $item->ledger_account_id ?? null,
                            'name' => $item->name,
                            'grade_level' => $item->grade_level,
                            'step' => $item->step,
                            'calculation_type' => $item->calculation_type,
                            'value' => $item->value,
                            'is_active' => $item->is_active,
                            'notes' => $item->notes,
                            'created_at' => $item->created_at,
                            'updated_at' => $item->updated_at,
                        ],
                    );
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_item_types');
    }
};
