<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Question> */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'type' => QuestionType::Single,
            'prompt' => fake()->sentence().'?',
            'points' => 1,
        ];
    }

    /**
     * A question made for a quiz joins that course's bank and takes a slot, so
     * `->for($quiz)` still reads as "a question on this quiz".
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Question $question) {
            if (! $question->quiz_id) {
                return;
            }

            $quiz = Quiz::find($question->quiz_id);

            if (! $quiz) {
                return;
            }

            $question->update(['course_id' => $quiz->course()->id]);
            $quiz->addQuestion($question);
        });
    }

    public function multi(): static
    {
        return $this->state(fn () => ['type' => QuestionType::Multi]);
    }
}
