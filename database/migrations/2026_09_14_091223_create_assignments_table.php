<?php

use App\Models\Lesson;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Lesson::class)->unique()->constrained()->cascadeOnDelete();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('points')->default(100);
            // Days after the learner enrols, matching how drip is counted. Null
            // means no deadline; late work is accepted and flagged either way.
            $table->unsignedInteger('due_days')->nullable();
            $table->boolean('allow_file')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
