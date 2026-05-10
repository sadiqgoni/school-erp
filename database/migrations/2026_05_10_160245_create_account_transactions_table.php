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
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('transactionable', 'acct_txn_morph_idx');
            $table->string('transaction_number', 60);
            $table->date('transaction_date');
            $table->string('direction', 20);
            $table->decimal('amount', 14, 2);
            $table->string('description');
            $table->string('reference')->nullable();
            $table->string('status', 30)->default('posted');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'transaction_number']);
            $table->index(['school_id', 'transaction_date', 'direction'], 'acct_txn_school_date_direction_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
