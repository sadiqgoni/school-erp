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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            $table->string('expense_number', 60);
            $table->date('expense_date');
            $table->string('payee')->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 40)->default('cash');
            $table->string('reference')->nullable();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('approved');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'expense_number']);
            $table->index(['school_id', 'expense_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
