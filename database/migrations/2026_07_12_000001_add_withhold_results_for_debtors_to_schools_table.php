<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Opt-in and off by default: existing schools should not suddenly
            // start withholding results for anyone until they deliberately
            // turn this policy on.
            $table->boolean('withhold_results_for_debtors')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('withhold_results_for_debtors');
        });
    }
};
