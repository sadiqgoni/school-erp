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
        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_account_id')->constrained('ledger_accounts')->cascadeOnDelete();
            $table->foreignId('to_account_id')->constrained('ledger_accounts')->cascadeOnDelete();
            $table->string('transfer_number', 60);
            $table->date('transfer_date');
            $table->decimal('amount', 14, 2);
            $table->string('reference')->nullable();
            $table->string('status', 30)->default('posted');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'transfer_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_transfers');
    }
};
