<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_cards', function (Blueprint $table) {
            $table->unsignedSmallInteger('attendance_total_days')->default(0)->after('position');
            $table->unsignedSmallInteger('attendance_present_days')->default(0)->after('attendance_total_days');
            $table->unsignedSmallInteger('attendance_absent_days')->default(0)->after('attendance_present_days');
        });

        Schema::create('result_trait_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 30)->default('affective');
            $table->unsignedTinyInteger('max_rating')->default(5);
            $table->unsignedSmallInteger('position')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'category', 'name']);
            $table->index(['school_id', 'category', 'is_active']);
        });

        Schema::create('report_card_trait_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_trait_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->unique(['report_card_id', 'result_trait_item_id'], 'report_trait_unique');
            $table->index(['school_id', 'result_trait_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_trait_ratings');
        Schema::dropIfExists('result_trait_items');

        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropColumn([
                'attendance_total_days',
                'attendance_present_days',
                'attendance_absent_days',
            ]);
        });
    }
};
