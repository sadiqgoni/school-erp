<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_sheets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
        });

        Schema::table('staff', function (Blueprint $table): void {
            if (! Schema::hasColumn('staff', 'payroll_sheet_id')) {
                $table->foreignId('payroll_sheet_id')
                    ->nullable()
                    ->after('salary_step')
                    ->constrained('payroll_sheets')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table): void {
            if (Schema::hasColumn('staff', 'payroll_sheet_id')) {
                $table->dropConstrainedForeignId('payroll_sheet_id');
            }
        });

        Schema::dropIfExists('payroll_sheets');
    }
};
