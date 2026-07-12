<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables where a hard delete would destroy financial or enrollment
     * history that a school/regulator may need later. Soft-deleting these
     * keeps the record recoverable and out of normal queries.
     */
    protected array $tables = [
        'schools',
        'students',
        'student_invoices',
        'fee_payments',
        'account_transactions',
        'salary_postings',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropSoftDeletes();
            });
        }
    }
};
