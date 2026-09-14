<?php

namespace Database\Factories;

use App\Enums\GradeSource;
use App\Models\Course;
use App\Models\GradeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GradeItem> */
class GradeItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'name' => fake()->words(3, true),
            'source' => GradeSource::Manual,
            'max_points' => 100,
            'weight' => 1,
        ];
    }
}
