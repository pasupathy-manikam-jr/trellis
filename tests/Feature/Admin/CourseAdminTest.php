<?php

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

test('the admin area is closed to guests, students and instructors', function (?string $role, string $expect) {
    $request = $role
        ? $this->actingAs(User::factory()->create(['role' => UserRole::from($role)]))
        : $this;

    $response = $request->get('/admin/courses');

    $expect === 'redirect'
        ? $response->assertRedirect('/login')
        : $response->assertForbidden();
})->with([
    'guest' => [null, 'redirect'],
    'student' => ['student', 'forbidden'],
    'instructor' => ['instructor', 'forbidden'],
]);

test('an admin creates a course and is sent to the editor', function () {
    $this->actingAs($this->admin)
        ->post('/admin/courses', [
            'title' => 'Building an LMS',
            'price_cents' => 4900,
            'status' => 'draft',
        ])
        ->assertRedirect('/admin/courses/building-an-lms/edit');

    $course = Course::sole();

    expect($course->title)->toBe('Building an LMS')
        ->and($course->slug)->toBe('building-an-lms')
        ->and($course->instructor_id)->toBe($this->admin->id)
        ->and($course->status)->toBe(CourseStatus::Draft);
});

test('publishing stamps published_at once and keeps it on re-publish', function () {
    $course = Course::factory()->create(['status' => CourseStatus::Draft]);

    $payload = fn (string $status) => [
        'title' => $course->title,
        'price_cents' => 0,
        'status' => $status,
    ];

    $this->actingAs($this->admin)->patch("/admin/courses/{$course->slug}", $payload('published'));
    $first = $course->fresh()->published_at;

    expect($first)->not->toBeNull();

    $this->travel(1)->days();
    $this->actingAs($this->admin)->patch("/admin/courses/{$course->slug}", $payload('draft'));
    $this->actingAs($this->admin)->patch("/admin/courses/{$course->slug}", $payload('published'));

    expect($course->fresh()->published_at->timestamp)->toBe($first->timestamp);
});

test('a duplicate slug is rejected', function () {
    Course::factory()->create(['slug' => 'taken']);
    $course = Course::factory()->create();

    $this->actingAs($this->admin)
        ->patch("/admin/courses/{$course->slug}", [
            'title' => $course->title,
            'slug' => 'taken',
            'price_cents' => 0,
            'status' => 'draft',
        ])
        ->assertSessionHasErrors('slug');
});

test('sections and lessons are added through the editor', function () {
    $course = Course::factory()->create();

    $this->actingAs($this->admin)
        ->post("/admin/courses/{$course->slug}/sections", ['title' => 'Getting started'])
        ->assertRedirect();

    $section = Section::sole();

    $this->actingAs($this->admin)
        ->post("/admin/sections/{$section->id}/lessons", [
            'title' => 'Install Laravel',
            'type' => 'text',
            'content' => 'Run composer create-project.',
            'is_preview' => true,
        ])
        ->assertRedirect();

    $lesson = Lesson::sole();

    expect($section->title)->toBe('Getting started')
        ->and($lesson->title)->toBe('Install Laravel')
        ->and($lesson->slug)->toBe('install-laravel')
        ->and($lesson->is_preview)->toBeTrue()
        ->and($lesson->position)->toBe(1);
});

test('moving a lesson through the endpoint reorders it', function () {
    $section = Section::factory()->create();
    Lesson::factory()->for($section)->create(['title' => 'A']);
    $b = Lesson::factory()->for($section)->create(['title' => 'B']);

    $this->actingAs($this->admin)
        ->patch("/admin/lessons/{$b->id}/move", ['direction' => 'up'])
        ->assertRedirect();

    expect($section->lessons()->pluck('title')->all())->toBe(['B', 'A']);
});

test('an invalid move direction is rejected', function () {
    $lesson = Lesson::factory()->create();

    $this->actingAs($this->admin)
        ->patch("/admin/lessons/{$lesson->id}/move", ['direction' => 'sideways'])
        ->assertSessionHasErrors('direction');
});

test('deleting a lesson soft-deletes it', function () {
    $lesson = Lesson::factory()->create();

    $this->actingAs($this->admin)->delete("/admin/lessons/{$lesson->id}")->assertRedirect();

    expect(Lesson::count())->toBe(0)
        ->and(Lesson::withTrashed()->count())->toBe(1);
});

test('every admin write route rejects a student', function () {
    $student = User::factory()->create(['role' => UserRole::Student]);
    $course = Course::factory()->create();
    $section = Section::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($section)->create();

    $routes = [
        ['post', '/admin/courses'],
        ['patch', "/admin/courses/{$course->slug}"],
        ['delete', "/admin/courses/{$course->slug}"],
        ['post', "/admin/courses/{$course->slug}/sections"],
        ['patch', "/admin/sections/{$section->id}"],
        ['delete', "/admin/sections/{$section->id}"],
        ['post', "/admin/sections/{$section->id}/lessons"],
        ['patch', "/admin/lessons/{$lesson->id}"],
        ['delete', "/admin/lessons/{$lesson->id}"],
    ];

    foreach ($routes as [$method, $url]) {
        $this->actingAs($student)->call($method, $url)->assertForbidden("{$method} {$url}");
    }
});
