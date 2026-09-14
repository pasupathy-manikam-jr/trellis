<?php

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->teacher = User::factory()->create(['role' => UserRole::Instructor]);
    $this->rival = User::factory()->create(['role' => UserRole::Instructor]);

    $this->mine = Course::factory()->create(['instructor_id' => $this->teacher->id]);
    $this->theirs = Course::factory()->create(['instructor_id' => $this->rival->id]);
});

test('an instructor reaches the workspace and sees only their own courses', function () {
    $this->actingAs($this->teacher)
        ->get('/admin/courses')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('courses', 1)
            ->where('courses.0.id', $this->mine->id)
        );
});

test('an admin still sees every course', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->get('/admin/courses')
        ->assertInertia(fn ($page) => $page->count('courses', 2));
});

test('a student is still refused the workspace', function () {
    $this->actingAs(User::factory()->create())->get('/admin/courses')->assertForbidden();
});

test('a guest is sent to log in', function () {
    // Separate test: actingAs persists for the rest of a test once called.
    $this->get('/admin/courses')->assertRedirect('/login');
});

test('an instructor owns what they create', function () {
    $this->actingAs($this->teacher)
        ->post('/admin/courses', ['title' => 'Mine alone', 'price_cents' => 0, 'status' => 'draft'])
        ->assertRedirect();

    expect(Course::where('title', 'Mine alone')->sole()->instructor_id)->toBe($this->teacher->id);
});

test('an instructor cannot open or change another instructor course', function () {
    $this->actingAs($this->teacher)->get("/admin/courses/{$this->theirs->slug}/edit")->assertForbidden();

    $this->actingAs($this->teacher)
        ->patch("/admin/courses/{$this->theirs->slug}", [
            'title' => 'Hijacked',
            'price_cents' => 0,
            'status' => 'draft',
        ])
        ->assertForbidden();

    $this->actingAs($this->teacher)->delete("/admin/courses/{$this->theirs->slug}")->assertForbidden();

    expect($this->theirs->fresh()->title)->not->toBe('Hijacked');
});

test('the whole nested tree of another course is closed too', function () {
    $section = Section::factory()->for($this->theirs)->create();
    $lesson = Lesson::factory()->for($section)->create();
    $quiz = Quiz::factory()->create(['lesson_id' => $lesson->id]);
    $question = Question::factory()->for($quiz)->create();
    Option::factory()->for($question)->create(['is_correct' => true]);
    Option::factory()->for($question)->create();
    $enrollment = Enrollment::factory()->for($this->theirs)->create();

    $blocked = [
        ['post', "/admin/courses/{$this->theirs->slug}/sections"],
        ['patch', "/admin/sections/{$section->id}"],
        ['patch', "/admin/sections/{$section->id}/move"],
        ['delete', "/admin/sections/{$section->id}"],
        ['post', "/admin/sections/{$section->id}/lessons"],
        ['patch', "/admin/lessons/{$lesson->id}"],
        ['patch', "/admin/lessons/{$lesson->id}/move"],
        ['delete', "/admin/lessons/{$lesson->id}"],
        ['post', "/admin/lessons/{$lesson->id}/quiz"],
        ['patch', "/admin/quizzes/{$quiz->id}"],
        ['post', "/admin/quizzes/{$quiz->id}/questions"],
        ['patch', "/admin/questions/{$question->id}"],
        ['delete', "/admin/questions/{$question->id}"],
        ['post', "/admin/quizzes/{$quiz->id}/questions/attach"],
        ['patch', "/admin/quizzes/{$quiz->id}/questions/{$question->id}/move"],
        ['delete', "/admin/quizzes/{$quiz->id}/questions/{$question->id}"],
        ['post', "/admin/courses/{$this->theirs->slug}/enrollments"],
        ['delete', "/admin/enrollments/{$enrollment->id}"],
    ];

    foreach ($blocked as [$method, $url]) {
        $this->actingAs($this->teacher)->call($method, $url)->assertForbidden("{$method} {$url}");
    }
});

test('an instructor can build out their own course', function () {
    $this->actingAs($this->teacher)
        ->post("/admin/courses/{$this->mine->slug}/sections", ['title' => 'Part one'])
        ->assertRedirect();

    $section = Section::sole();

    $this->actingAs($this->teacher)
        ->post("/admin/sections/{$section->id}/lessons", ['title' => 'Opening', 'type' => 'text'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Lesson::sole()->title)->toBe('Opening');
});

test('insights show an instructor only their own courses', function () {
    $this->actingAs($this->teacher)
        ->get('/admin/insights')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('courses', 1)
            ->where('courses.0.id', $this->mine->id)
        );
});

test('money, taxonomy and people stay with admins', function () {
    foreach ([['get', '/admin/orders'], ['get', '/admin/coupons'], ['get', '/admin/categories'], ['get', '/admin/users']] as [$method, $url]) {
        $this->actingAs($this->teacher)->call($method, $url)->assertForbidden($url);
    }
});

test('an admin promotes someone to instructor', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $learner = User::factory()->create();

    $this->actingAs($admin)
        ->patch("/admin/users/{$learner->id}/role", ['role' => 'instructor'])
        ->assertRedirect();

    expect($learner->fresh()->role)->toBe(UserRole::Instructor);

    // The in-memory model still says "student" until it is reloaded.
    $this->actingAs($learner->fresh())->get('/admin/courses')->assertOk();
});

test('an admin cannot demote themselves out of admin', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->patch("/admin/users/{$admin->id}/role", ['role' => 'student'])
        ->assertSessionHasErrors('role');

    expect($admin->fresh()->role)->toBe(UserRole::Admin);
});

test('an instructor cannot promote anyone', function () {
    $learner = User::factory()->create();

    $this->actingAs($this->teacher)
        ->patch("/admin/users/{$learner->id}/role", ['role' => 'admin'])
        ->assertForbidden();

    expect($learner->fresh()->role)->toBe(UserRole::Student);
});

test('an admin lands on their workspace rather than a learner dashboard', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    // Covers every way in: signing in, registering, verifying an email, an old
    // bookmark — they all end at /dashboard.
    $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin/courses');
});

test('instructors and learners keep their dashboard', function () {
    $this->actingAs($this->teacher)->get('/dashboard')->assertOk();
    $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk();
});
