<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QuizAttempt> */
class QuizAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quiz_id' => Quiz::factory(),
            'score_percent' => 100,
            'points_earned' => 1,
            'points_possible' => 1,
            'passed' => true,
            'submitted_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => ['score_percent' => 0, 'points_earned' => 0, 'passed' => false]);
    }
}
