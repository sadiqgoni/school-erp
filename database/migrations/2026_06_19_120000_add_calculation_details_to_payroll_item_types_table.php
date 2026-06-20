<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_item_types', function (Blueprint $table): void {
            if (! Schema::hasColumn('payroll_item_types', 'calculation_details')) {
                $table->json('calculation_details')->nullable()->after('calculation_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_item_types', function (Blueprint $table): void {
            if (Schema::hasColumn('payroll_item_types', 'calculation_details')) {
                $table->dropColumn('calculation_details');
            }
        });
    }
};
