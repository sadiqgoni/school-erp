<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('staff', 'salary_template_id')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table): void {
            $table->foreignId('salary_template_id')
                ->nullable()
                ->after('basic_salary')
                ->constrained('salary_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('staff', 'salary_template_id')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('salary_template_id');
        });
    }
};
