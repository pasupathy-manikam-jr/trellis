<?php

use App\Models\Category;
use App\Models\Course;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // A course can sit in more than one — "Backend" and "Databases" is a
        // real pairing, not a hypothetical.
        Schema::create('category_course', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Category::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Course::class)->constrained()->cascadeOnDelete();

            $table->unique(['category_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_course');
        Schema::dropIfExists('categories');
    }
};
