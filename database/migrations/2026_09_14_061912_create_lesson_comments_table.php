<?php

use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Lesson::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();

            // One level only: a question, and answers to it. Threads that nest
            // deeper get harder to read, not more useful.
            $table->foreignIdFor(LessonComment::class, 'parent_id')
                ->nullable()
                ->constrained('lesson_comments')
                ->cascadeOnDelete();

            $table->text('body');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['lesson_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_comments');
    }
};
