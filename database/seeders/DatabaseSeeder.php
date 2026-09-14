<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentSource;
use App\Enums\LessonType;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@lms.test'],
            ['name' => 'Admin', 'password' => 'password', 'role' => UserRole::Admin],
        );

        $student = User::updateOrCreate(
            ['email' => 'student@lms.test'],
            ['name' => 'Student', 'password' => 'password', 'role' => UserRole::Student],
        );

        if (Course::exists()) {
            return;
        }

        $course = Course::create([
            'instructor_id' => $admin->id,
            'title' => 'Building an LMS with Laravel',
            'summary' => 'Design the schema, build the course editor, ship the player.',
            'description' => "A worked example that follows this repository's own plan.\n\n"
                .'Start with the data model, add an admin that makes it editable, then open it to learners.',
            'price_cents' => 4900,
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);

        $outline = [
            'Foundations' => [
                ['Why not fork Moodle', LessonType::Text, true],
                ['Schema: courses, sections, lessons', LessonType::Text, true],
                ['Slugs and ordering', LessonType::Text, false],
            ],
            'The admin' => [
                ['Route groups and role middleware', LessonType::Text, false],
                ['The course editor', LessonType::Video, false],
            ],
            'Going public' => [
                ['Catalog and detail pages', LessonType::Text, false],
                ['What never reaches the browser', LessonType::Text, false],
            ],
        ];

        foreach ($outline as $title => $lessons) {
            $section = Section::create(['course_id' => $course->id, 'title' => $title]);

            foreach ($lessons as [$lessonTitle, $type, $isPreview]) {
                Lesson::create([
                    'section_id' => $section->id,
                    'title' => $lessonTitle,
                    'type' => $type,
                    'content' => "Placeholder body for “{$lessonTitle}”.",
                    'duration_sec' => $type === LessonType::Video ? 540 : null,
                    'is_preview' => $isPreview,
                ]);
            }
        }

        // A free course so self-enrolment is demonstrable without checkout.
        $free = Course::create([
            'instructor_id' => $admin->id,
            'title' => 'Postgres for people who used MySQL',
            'summary' => 'A short free primer. Enrol in one click.',
            'price_cents' => 0,
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);

        $basics = Section::create(['course_id' => $free->id, 'title' => 'Basics']);

        foreach (['Types that actually exist', 'Sequences, not AUTO_INCREMENT', 'JSONB'] as $i => $title) {
            Lesson::create([
                'section_id' => $basics->id,
                'title' => $title,
                'type' => LessonType::Text,
                'content' => "Placeholder body for “{$title}”.",
                'is_preview' => $i === 0,
            ]);
        }

        // The paid course is already bought, so the player is reachable on first look.
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'source' => EnrollmentSource::Manual,
            'started_at' => now(),
        ]);

        Course::factory()->create([
            'instructor_id' => $admin->id,
            'title' => 'A draft that should not appear publicly',
            'status' => CourseStatus::Draft,
        ]);
    }
}
