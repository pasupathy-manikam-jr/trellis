<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
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
            'title' => 'Building a Course Platform',
            'summary' => 'Design the schema, build the course editor, ship the player.',
            'description' => "A worked example that follows this repository's own plan.\n\n"
                .'Start with the data model, add an admin that makes it editable, then open it to learners.',
            'price_cents' => 4900,
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);

        $outline = [
            'Foundations' => [
                ['Why not fork an existing platform', LessonType::Text, true],
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

        $this->seedQuiz($course);

        // Left unbought on purpose: the paid course is the one to try checkout on.
        Coupon::create(['code' => 'LAUNCH20', 'percent_off' => 20]);
        Coupon::create(['code' => 'HALFOFF', 'percent_off' => 50, 'max_redemptions' => 2]);
        Coupon::create(['code' => 'TENOFF', 'amount_off_cents' => 1000]);
        Coupon::create(['code' => 'LASTYEAR', 'percent_off' => 90, 'expires_at' => now()->subDay()]);

        Course::factory()->create([
            'instructor_id' => $admin->id,
            'title' => 'A draft that should not appear publicly',
            'status' => CourseStatus::Draft,
        ]);
    }

    /** A short quiz on the last section, so finishing the course means passing it. */
    private function seedQuiz(Course $course): void
    {
        $lesson = Lesson::create([
            'section_id' => $course->sections()->orderByDesc('position')->first()->id,
            'title' => 'Check what you learned',
            'type' => LessonType::Quiz,
        ]);

        $quiz = Quiz::create(['lesson_id' => $lesson->id, 'pass_percent' => 70, 'max_attempts' => 3]);

        $bank = [
            ['Where should gated video files live?', QuestionType::Single, [
                ['storage/app/private — behind an access check', true],
                ['storage/app/public — it is faster', false],
                ['In the database as a blob', false],
            ]],
            ['Which belong in a course-selling LMS? Pick all.', QuestionType::Multi, [
                ['Enrolments', true],
                ['A gradebook with weighted categories', false],
                ['Certificates', true],
                ['SCORM packages', false],
            ]],
            ['Why is progress computed rather than stored?', QuestionType::Single, [
                ['Editing a course cannot leave a stale percentage', true],
                ['It is faster to query', false],
                ['Postgres cannot store integers', false],
            ]],
        ];

        foreach ($bank as [$prompt, $type, $options]) {
            $question = Question::create([
                'quiz_id' => $quiz->id,
                'type' => $type,
                'prompt' => $prompt,
                'points' => $type === QuestionType::Multi ? 2 : 1,
            ]);

            foreach ($options as [$text, $correct]) {
                Option::create([
                    'question_id' => $question->id,
                    'text' => $text,
                    'is_correct' => $correct,
                ]);
            }
        }
    }
}
