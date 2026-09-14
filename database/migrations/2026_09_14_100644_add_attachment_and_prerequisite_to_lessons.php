<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // A download lesson had a type and an icon but nothing to download.
            $table->string('attachment_path')->nullable()->after('video_path');
            $table->string('attachment_name')->nullable()->after('attachment_path');

            // "Opens once you have finished that one." Self-referencing, and
            // nulled rather than cascaded so deleting a prerequisite unlocks
            // what depended on it instead of deleting it too.
            $table->foreignId('requires_lesson_id')->nullable()->after('drip_days')
                ->constrained('lessons')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requires_lesson_id');
            $table->dropColumn(['attachment_path', 'attachment_name']);
        });
    }
};
