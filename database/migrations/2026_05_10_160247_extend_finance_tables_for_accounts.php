<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_types', function (Blueprint $table): void {
            $table->foreignId('billing_category_id')->nullable()->after('school_id')->constrained()->nullOnDelete();
        });

        Schema::table('student_invoices', function (Blueprint $table): void {
            $table->string('invoice_type', 30)->default('standard')->after('invoice_number');
            $table->foreignId('student_discount_id')->nullable()->after('term_id')->constrained()->nullOnDelete();
            $table->foreignId('income_account_id')->nullable()->after('student_discount_id')->constrained('ledger_accounts')->nullOnDelete();
        });

        Schema::table('fee_payments', function (Blueprint $table): void {
            $table->string('payer')->nullable()->after('receipt_number');
            $table->foreignId('bank_account_id')->nullable()->after('payment_method')->constrained()->nullOnDelete();
            $table->foreignId('asset_account_id')->nullable()->after('bank_account_id')->constrained('ledger_accounts')->nullOnDelete();
            $table->foreignId('income_account_id')->nullable()->after('asset_account_id')->constrained('ledger_accounts')->nullOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->foreignId('bank_account_id')->nullable()->after('payment_method')->constrained()->nullOnDelete();
            $table->foreignId('asset_account_id')->nullable()->after('bank_account_id')->constrained('ledger_accounts')->nullOnDelete();
            $table->foreignId('expense_account_id')->nullable()->after('asset_account_id')->constrained('ledger_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('expense_account_id');
            $table->dropConstrainedForeignId('asset_account_id');
            $table->dropConstrainedForeignId('bank_account_id');
        });

        Schema::table('fee_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('income_account_id');
            $table->dropConstrainedForeignId('asset_account_id');
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropColumn('payer');
        });

        Schema::table('student_invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('income_account_id');
            $table->dropConstrainedForeignId('student_discount_id');
            $table->dropColumn('invoice_type');
        });

        Schema::table('fee_types', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('billing_category_id');
        });
    }
};
