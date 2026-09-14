<?php

use App\Models\Lesson;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Lesson::class)->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('pass_percent')->default(70);
            $table->unsignedTinyInteger('max_attempts')->nullable();
            $table->boolean('shuffle')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
