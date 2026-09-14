<?php

use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\Quiz;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lifts questions out of the quiz that happened to need them first.
 *
 * A question now belongs to a course's bank and is used by a quiz through a
 * slot, so the same question can appear in a practice quiz and a final without
 * being written twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Course::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['course_id', 'position']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignIdFor(Course::class)->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(QuestionCategory::class)->nullable()->after('course_id')
                ->constrained()->nullOnDelete();
        });

        // The slot: which questions a quiz asks, and in what order.
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Quiz::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Question::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);

            $table->unique(['quiz_id', 'question_id']);
            $table->index(['quiz_id', 'position']);
        });

        // Everything already written keeps working: each question joins the bank
        // of the course whose quiz held it, and takes a slot in that quiz.
        DB::table('questions')->orderBy('id')->chunkById(200, function ($questions) {
            foreach ($questions as $question) {
                $courseId = DB::table('quizzes')
                    ->join('lessons', 'lessons.id', '=', 'quizzes.lesson_id')
                    ->join('sections', 'sections.id', '=', 'lessons.section_id')
                    ->where('quizzes.id', $question->quiz_id)
                    ->value('sections.course_id');

                DB::table('questions')->where('id', $question->id)->update(['course_id' => $courseId]);

                DB::table('quiz_questions')->insert([
                    'quiz_id' => $question->quiz_id,
                    'question_id' => $question->id,
                    'position' => $question->position,
                ]);
            }
        });

        // quiz_id stays for now as the question's origin, but membership is the
        // slot table's job from here.
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedBigInteger('quiz_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');

        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_id');
            $table->dropConstrainedForeignId('question_category_id');
        });

        Schema::dropIfExists('question_categories');
    }
};
