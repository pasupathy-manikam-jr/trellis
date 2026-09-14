<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use Inertia\Testing\AssertableInertia;

test('the catalog lists only published courses', function () {
    $published = Course::factory()->published()->create(['title' => 'Live course']);
    Course::factory()->create(['title' => 'Draft course']);

    $this->get('/courses')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/index')
            ->has('courses', 1)
            ->where('courses.0.title', $published->title)
        );
});

test('a draft course is not reachable by URL', function () {
    $course = Course::factory()->create();

    $this->get("/courses/{$course->slug}")->assertNotFound();
});

test('a published course shows its outline to guests', function () {
    $course = Course::factory()->published()->create();
    $section = Section::factory()->for($course)->create(['title' => 'Module one']);
    Lesson::factory()->for($section)->create(['title' => 'First lesson']);

    $this->get("/courses/{$course->slug}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/show')
            ->where('course.sections.0.title', 'Module one')
            ->where('course.sections.0.lessons.0.title', 'First lesson')
        );
});

test('the public outline never exposes lesson bodies or video paths', function () {
    $course = Course::factory()->published()->create();
    $section = Section::factory()->for($course)->create();
    Lesson::factory()->for($section)->video()->create([
        'content' => 'SECRET-LESSON-BODY',
        'video_path' => 'videos/secret.mp4',
    ]);

    $response = $this->get("/courses/{$course->slug}")->assertOk();

    $response->assertDontSee('SECRET-LESSON-BODY')
        ->assertDontSee('videos/secret.mp4');

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->missing('course.sections.0.lessons.0.content')
        ->missing('course.sections.0.lessons.0.video_path')
    );
});
