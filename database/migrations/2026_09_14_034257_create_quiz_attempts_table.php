<?php

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Quiz::class)->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score_percent');
            $table->unsignedInteger('points_earned')->default(0);
            $table->unsignedInteger('points_possible')->default(0);
            $table->boolean('passed')->default(false);
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
