<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('staff', 'staff_bank_id')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table): void {
            $table->foreignId('staff_bank_id')
                ->nullable()
                ->after('basic_salary')
                ->constrained('staff_banks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('staff', 'staff_bank_id')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('staff_bank_id');
        });
    }
};
