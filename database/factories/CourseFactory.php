<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Course> */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'instructor_id' => User::factory()->state(['role' => UserRole::Instructor]),
            'title' => $title,
            'slug' => null,
            'summary' => fake()->sentence(12),
            'description' => fake()->paragraphs(3, true),
            'price_cents' => fake()->randomElement([0, 2900, 4900, 9900]),
            'currency' => 'USD',
            'status' => CourseStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function free(): static
    {
        return $this->state(fn () => ['price_cents' => 0]);
    }
}
