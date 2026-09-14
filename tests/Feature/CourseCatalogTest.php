<?php

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('the catalog lists only published courses', function () {
    $published = Course::factory()->published()->create(['title' => 'Live course']);
    Course::factory()->create(['title' => 'Draft course']);

    $this->get('/courses')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/index')
            ->where('courses.total', 1)
            ->where('courses.data.0.title', $published->title)
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

test('the splash page shows published courses only, and works logged out', function () {
    Course::factory()->published()->create(['title' => 'Live one']);
    Course::factory()->create(['title' => 'Draft one']);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('welcome')
            ->has('courses', 1)
            ->where('courses.0.title', 'Live one')
        )
        ->assertDontSee('Draft one');
});

test('the splash page shows at most three courses', function () {
    Course::factory()->count(5)->published()->create();

    $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page->has('courses', 3));
});

test('a preview lesson can be opened straight from the course page by anyone', function () {
    $course = Course::factory()->published()->create();
    $section = Section::factory()->for($course)->create();
    $preview = Lesson::factory()->for($section)->create(['is_preview' => true]);
    Lesson::factory()->for($section)->create(['is_preview' => false]);

    $this->get("/courses/{$course->slug}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('course.sections.0.lessons.0.is_preview', true)
            ->where('can_preview_all', false)
        );

    $this->get("/learn/{$course->slug}/{$preview->id}")->assertOk();
});

test('the course owner may click into any lesson, not just previews', function () {
    $course = Course::factory()->published()->create();
    Lesson::factory()->for(Section::factory()->for($course))->create();

    $this->actingAs($course->instructor)
        ->get("/courses/{$course->slug}")
        ->assertInertia(fn (AssertableInertia $page) => $page->where('can_preview_all', true));
});

test('toggling preview from the editor flips it both ways', function () {
    $course = Course::factory()->create();
    $lesson = Lesson::factory()
        ->for(Section::factory()->for($course))
        ->create(['is_preview' => false]);

    $this->actingAs($course->instructor)
        ->patch("/admin/lessons/{$lesson->id}/preview")
        ->assertRedirect();

    expect($lesson->fresh()->is_preview)->toBeTrue();

    $this->actingAs($course->instructor)->patch("/admin/lessons/{$lesson->id}/preview");

    expect($lesson->fresh()->is_preview)->toBeFalse();
});

test('someone else cannot open up your lessons', function () {
    $lesson = Lesson::factory()->create();

    $this->actingAs(User::factory()->create(['role' => UserRole::Instructor]))
        ->patch("/admin/lessons/{$lesson->id}/preview")
        ->assertForbidden();

    expect($lesson->fresh()->is_preview)->toBeFalse();
});
