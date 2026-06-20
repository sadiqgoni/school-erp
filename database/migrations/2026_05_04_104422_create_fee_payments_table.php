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
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number', 60);
            $table->string('payer')->nullable();
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 40)->default('cash');
            $table->string('payment_provider', 40)->nullable();
            $table->string('provider_transaction_id')->nullable();
            $table->json('provider_payload')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_account_id')->nullable()->constrained('ledger_accounts')->nullOnDelete();
            $table->foreignId('income_account_id')->nullable()->constrained('ledger_accounts')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->foreignId('received_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('confirmed');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'receipt_number']);
            $table->index(['school_id', 'student_id', 'payment_date'], 'fee_payment_school_student_date_idx');
            $table->index(['school_id', 'payment_provider', 'provider_transaction_id'], 'fee_payment_provider_txn_idx');
            $table->index(['school_id', 'status', 'acknowledged_at'], 'fee_payment_acknowledgement_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
    }
};
