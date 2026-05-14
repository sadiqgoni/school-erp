<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->foreignId('parent_school_id')
                ->nullable()
                ->after('id')
                ->constrained('schools')
                ->nullOnDelete();
            $table->string('division', 30)
                ->nullable()
                ->after('parent_school_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_school_id');
            $table->dropColumn('division');
        });
    }
};
