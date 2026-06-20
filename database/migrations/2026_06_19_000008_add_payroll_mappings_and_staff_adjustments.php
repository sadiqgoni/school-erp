<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_allowances', function (Blueprint $table): void {
            if (! Schema::hasColumn('salary_allowances', 'ledger_account_id')) {
                $table->foreignId('ledger_account_id')
                    ->nullable()
                    ->after('salary_template_id')
                    ->constrained('ledger_accounts')
                    ->nullOnDelete();
            }
        });

        Schema::table('salary_deductions', function (Blueprint $table): void {
            if (! Schema::hasColumn('salary_deductions', 'ledger_account_id')) {
                $table->foreignId('ledger_account_id')
                    ->nullable()
                    ->after('salary_template_id')
                    ->constrained('ledger_accounts')
                    ->nullOnDelete();
            }
        });

        Schema::create('staff_salary_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->nullable()->constrained('ledger_accounts')->nullOnDelete();
            $table->string('type', 20);
            $table->string('code', 50);
            $table->string('name');
            $table->string('calculation_type', 30)->default('fixed');
            $table->decimal('value', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'staff_id', 'type']);
            $table->unique(['staff_id', 'type', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_salary_adjustments');

        Schema::table('salary_deductions', function (Blueprint $table): void {
            if (Schema::hasColumn('salary_deductions', 'ledger_account_id')) {
                $table->dropConstrainedForeignId('ledger_account_id');
            }
        });

        Schema::table('salary_allowances', function (Blueprint $table): void {
            if (Schema::hasColumn('salary_allowances', 'ledger_account_id')) {
                $table->dropConstrainedForeignId('ledger_account_id');
            }
        });
    }
};
