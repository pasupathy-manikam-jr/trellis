<?php

use App\Models\Section;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Section::class)->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->string('type')->default('text');
            $table->text('content')->nullable();
            $table->string('video_path')->nullable();
            $table->unsignedInteger('duration_sec')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->unsignedInteger('drip_days')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['section_id', 'position']);
            $table->unique(['section_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
