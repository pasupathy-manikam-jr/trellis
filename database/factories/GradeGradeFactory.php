<?php

namespace Database\Factories;

use App\Models\GradeGrade;
use App\Models\GradeItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GradeGrade> */
class GradeGradeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'grade_item_id' => GradeItem::factory(),
            'user_id' => User::factory(),
            'points' => fake()->numberBetween(50, 100),
            'graded_at' => now(),
        ];
    }
}
