<?php

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Course::class)->constrained()->cascadeOnDelete();

            // The activity being graded. Null for a column the instructor keeps
            // by hand — participation, an oral, anything off-platform.
            $table->foreignIdFor(Lesson::class)->nullable()->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('source')->default('manual');   // quiz | assignment | manual
            $table->unsignedInteger('max_points')->default(100);
            // Relative, not a percentage: weights are normalised against the
            // course total, so they never have to add up to anything.
            $table->unsignedInteger('weight')->default(1);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['course_id', 'lesson_id']);
            $table->index(['course_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_items');
    }
};
