<?php

use App\Enums\EnrollmentSource;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;

function courseWithLessons(int $count = 3, array $attributes = []): Course
{
    $course = Course::factory()->published()->create($attributes);
    $section = Section::factory()->for($course)->create();
    Lesson::factory()->count($count)->for($section)->create();

    return $course;
}

test('a learner enrols themselves in a free course', function () {
    $course = courseWithLessons(attributes: ['price_cents' => 0]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post("/courses/{$course->slug}/enroll")
        ->assertRedirect("/learn/{$course->slug}");

    $enrollment = Enrollment::sole();

    expect($enrollment->user_id)->toBe($user->id)
        ->and($enrollment->source)->toBe(EnrollmentSource::Free);
});

test('a paid course cannot be self-enrolled', function () {
    $course = courseWithLessons(attributes: ['price_cents' => 4900]);

    $this->actingAs(User::factory()->create())
        ->post("/courses/{$course->slug}/enroll")
        ->assertForbidden();

    expect(Enrollment::count())->toBe(0);
});

test('a draft course cannot be enrolled in', function () {
    $course = Course::factory()->free()->create();

    $this->actingAs(User::factory()->create())
        ->post("/courses/{$course->slug}/enroll")
        ->assertForbidden();
});

test('enrolling twice is refused', function () {
    $course = courseWithLessons(attributes: ['price_cents' => 0]);
    $user = User::factory()->create();

    $this->actingAs($user)->post("/courses/{$course->slug}/enroll");
    $this->actingAs($user)->post("/courses/{$course->slug}/enroll")->assertForbidden();

    expect(Enrollment::count())->toBe(1);
});

test('guests are sent to log in rather than enrolled', function () {
    $course = courseWithLessons(attributes: ['price_cents' => 0]);

    $this->post("/courses/{$course->slug}/enroll")->assertRedirect('/login');
});

test('the course entry point lands on the first lesson', function () {
    $course = courseWithLessons();
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    $first = $course->sections()->first()->lessons()->first();

    $this->actingAs($user)
        ->get("/learn/{$course->slug}")
        ->assertRedirect("/learn/{$course->slug}/{$first->id}");
});

test('progress rises with completions and completes the course at the end', function () {
    $course = courseWithLessons(3);
    $user = User::factory()->create();
    $enrollment = Enrollment::factory()->for($user)->for($course)->create();
    $lessons = $course->sections()->first()->lessons;

    expect($enrollment->progress())->toBe(['completed' => 0, 'total' => 3, 'percent' => 0]);

    $this->actingAs($user)->post("/lessons/{$lessons[0]->id}/complete")->assertRedirect();
    expect($enrollment->fresh()->progress()['percent'])->toBe(33)
        ->and($enrollment->fresh()->completed_at)->toBeNull();

    $this->actingAs($user)->post("/lessons/{$lessons[1]->id}/complete");
    $this->actingAs($user)->post("/lessons/{$lessons[2]->id}/complete");

    $enrollment->refresh();

    expect($enrollment->progress())->toBe(['completed' => 3, 'total' => 3, 'percent' => 100])
        ->and($enrollment->completed_at)->not->toBeNull();
});

test('un-completing a lesson reopens a finished course', function () {
    $course = courseWithLessons(1);
    $user = User::factory()->create();
    $enrollment = Enrollment::factory()->for($user)->for($course)->create();
    $lesson = $course->sections()->first()->lessons()->sole();

    $this->actingAs($user)->post("/lessons/{$lesson->id}/complete");
    expect($enrollment->fresh()->completed_at)->not->toBeNull();

    $this->actingAs($user)->delete("/lessons/{$lesson->id}/complete");
    expect($enrollment->fresh()->completed_at)->toBeNull()
        ->and($enrollment->fresh()->progress()['percent'])->toBe(0);
});

test('completing the same lesson twice counts once', function () {
    $course = courseWithLessons(2);
    $user = User::factory()->create();
    $enrollment = Enrollment::factory()->for($user)->for($course)->create();
    $lesson = $course->sections()->first()->lessons()->first();

    $this->actingAs($user)->post("/lessons/{$lesson->id}/complete");
    $this->actingAs($user)->post("/lessons/{$lesson->id}/complete");

    expect($enrollment->progress()['completed'])->toBe(1);
});

test('someone not enrolled cannot record progress', function () {
    $course = courseWithLessons(1);
    $lesson = $course->sections()->first()->lessons()->sole();

    $this->actingAs(User::factory()->create())
        ->post("/lessons/{$lesson->id}/complete")
        ->assertForbidden();
});

test('an admin enrols someone manually and can revoke it', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $course = courseWithLessons(attributes: ['price_cents' => 9900]);
    $student = User::factory()->create(['email' => 'buyer@lms.test']);

    $this->actingAs($admin)
        ->post("/admin/courses/{$course->slug}/enrollments", ['email' => 'buyer@lms.test'])
        ->assertRedirect();

    $enrollment = Enrollment::sole();
    expect($enrollment->user_id)->toBe($student->id)
        ->and($enrollment->source)->toBe(EnrollmentSource::Manual);

    $this->actingAs($admin)->delete("/admin/enrollments/{$enrollment->id}")->assertRedirect();
    expect(Enrollment::count())->toBe(0);
});

test('manual enrolment rejects an unknown email and a duplicate', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $course = courseWithLessons();
    $student = User::factory()->create(['email' => 'dupe@lms.test']);
    Enrollment::factory()->for($student)->for($course)->create();

    $this->actingAs($admin)
        ->post("/admin/courses/{$course->slug}/enrollments", ['email' => 'nobody@lms.test'])
        ->assertSessionHasErrors('email');

    $this->actingAs($admin)
        ->post("/admin/courses/{$course->slug}/enrollments", ['email' => 'dupe@lms.test'])
        ->assertSessionHasErrors('email');

    expect(Enrollment::count())->toBe(1);
});
