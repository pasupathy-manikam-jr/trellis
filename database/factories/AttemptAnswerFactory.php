<?php

namespace Database\Factories;

use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttemptAnswer> */
class AttemptAnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_attempt_id' => QuizAttempt::factory(),
            'question_id' => Question::factory(),
            'option_ids' => [],
            'is_correct' => false,
        ];
    }
}
