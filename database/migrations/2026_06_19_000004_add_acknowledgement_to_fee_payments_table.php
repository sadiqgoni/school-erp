<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('fee_payments', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('fee_payments', 'acknowledged_by_id')) {
                $table->foreignId('acknowledged_by_id')
                    ->nullable()
                    ->after('acknowledged_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        if (! Schema::hasIndex('fee_payments', 'fee_payment_acknowledgement_idx')) {
            Schema::table('fee_payments', function (Blueprint $table): void {
                $table->index(['school_id', 'status', 'acknowledged_at'], 'fee_payment_acknowledgement_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('fee_payments', 'fee_payment_acknowledgement_idx')) {
            Schema::table('fee_payments', function (Blueprint $table): void {
                $table->dropIndex('fee_payment_acknowledgement_idx');
            });
        }

        Schema::table('fee_payments', function (Blueprint $table): void {
            if (Schema::hasColumn('fee_payments', 'acknowledged_by_id')) {
                $table->dropConstrainedForeignId('acknowledged_by_id');
            }

            if (Schema::hasColumn('fee_payments', 'acknowledged_at')) {
                $table->dropColumn('acknowledged_at');
            }
        });
    }
};
