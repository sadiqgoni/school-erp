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
        Schema::table('notices', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('title')->after('school_id');
            $table->longText('body')->nullable()->after('title');
            $table->string('attachment_path')->nullable()->after('body');
            $table->string('audience_type', 30)->default('all')->after('attachment_path');
            $table->string('audience_division', 60)->nullable()->after('audience_type');
            $table->foreignId('school_class_id')->nullable()->after('audience_division')->constrained()->nullOnDelete();
            $table->foreignId('class_section_id')->nullable()->after('school_class_id')->constrained()->nullOnDelete();
            $table->string('category', 40)->default('general')->after('class_section_id');
            $table->boolean('is_pinned')->default(false)->after('category');
            $table->string('status', 30)->default('published')->after('is_pinned');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->date('expires_on')->nullable()->after('published_at');
            $table->foreignId('created_by')->nullable()->after('expires_on')->constrained('users')->nullOnDelete();

            $table->index(['school_id', 'status', 'published_at'], 'notices_school_status_published_idx');
            $table->index(['school_id', 'audience_type'], 'notices_school_audience_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropIndex('notices_school_status_published_idx');
            $table->dropIndex('notices_school_audience_idx');
            $table->dropConstrainedForeignId('school_id');
            $table->dropConstrainedForeignId('school_class_id');
            $table->dropConstrainedForeignId('class_section_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn([
                'title',
                'body',
                'attachment_path',
                'audience_type',
                'audience_division',
                'category',
                'is_pinned',
                'status',
                'published_at',
                'expires_on',
            ]);
        });
    }
};
