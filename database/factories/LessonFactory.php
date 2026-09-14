<?php

namespace Database\Factories;

use App\Enums\LessonType;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lesson> */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'title' => fake()->sentence(4),
            'slug' => null,
            'type' => LessonType::Text,
            'content' => fake()->paragraphs(4, true),
        ];
    }

    public function video(): static
    {
        return $this->state(fn () => [
            'type' => LessonType::Video,
            'content' => null,
            'video_path' => 'videos/sample.mp4',
            'duration_sec' => fake()->numberBetween(60, 1800),
        ]);
    }

    public function preview(): static
    {
        return $this->state(fn () => ['is_preview' => true]);
    }
}
