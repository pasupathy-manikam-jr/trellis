<?php

namespace Database\Factories;

use App\Enums\LessonType;
use App\Models\Assignment;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Assignment> */
class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory()->state(['type' => LessonType::Assignment]),
            'instructions' => fake()->paragraph(),
            'points' => 100,
        ];
    }
}
