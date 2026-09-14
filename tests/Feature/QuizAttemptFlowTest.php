<?php

use App\Enums\LessonType;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;

function courseWithQuiz(int $otherLessons = 0): array
{
    $course = Course::factory()->published()->create();
    $section = Section::factory()->for($course)->create();

    Lesson::factory()->count($otherLessons)->for($section)->create();

    $lesson = Lesson::factory()->for($section)->create(['type' => LessonType::Quiz]);
    $quiz = Quiz::factory()->create(['lesson_id' => $lesson->id, 'pass_percent' => 70]);
    $question = Question::factory()->for($quiz)->create();
    Option::factory()->for($question)->create(['is_correct' => true]);
    Option::factory()->for($question)->create(['is_correct' => false]);

    return [$course, $lesson, $quiz, $question];
}

function right(Question $q): array
{
    return [$q->id => $q->options()->where('is_correct', true)->pluck('id')->all()];
}

function wrong(Question $q): array
{
    return [$q->id => $q->options()->where('is_correct', false)->pluck('id')->all()];
}

test('a quiz lesson cannot be ticked complete by hand', function () {
    [$course, $lesson] = courseWithQuiz();
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    $this->actingAs($user)->post("/lessons/{$lesson->id}/complete")->assertForbidden();

    expect($user->completions()->count())->toBe(0);
});

test('failing a quiz records the attempt but completes nothing', function () {
    [$course, $lesson, $quiz, $question] = courseWithQuiz();
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    $this->actingAs($user)
        ->post("/lessons/{$lesson->id}/quiz", ['answers' => wrong($question)])
        ->assertRedirect();

    expect($quiz->attemptsBy($user)->count())->toBe(1)
        ->and($quiz->isPassedBy($user))->toBeFalse()
        ->and($user->completions()->count())->toBe(0);
});

test('passing a quiz completes its lesson', function () {
    [$course, $lesson, $quiz, $question] = courseWithQuiz(otherLessons: 1);
    $user = User::factory()->create();
    $enrollment = Enrollment::factory()->for($user)->for($course)->create();

    $this->actingAs($user)
        ->post("/lessons/{$lesson->id}/quiz", ['answers' => right($question)])
        ->assertRedirect();

    expect($quiz->isPassedBy($user))->toBeTrue()
        ->and($user->completions()->where('lesson_id', $lesson->id)->exists())->toBeTrue()
        ->and($enrollment->fresh()->progress()['percent'])->toBe(50);
});

test('someone not enrolled cannot sit the quiz', function () {
    [, $lesson, , $question] = courseWithQuiz();

    $this->actingAs(User::factory()->create())
        ->post("/lessons/{$lesson->id}/quiz", ['answers' => right($question)])
        ->assertForbidden();
});

test('a guest cannot sit the quiz', function () {
    [, $lesson, , $question] = courseWithQuiz();

    $this->post("/lessons/{$lesson->id}/quiz", ['answers' => right($question)])
        ->assertRedirect('/login');
});

test('the quiz cannot be retaken once passed', function () {
    [$course, $lesson, $quiz, $question] = courseWithQuiz();
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    $this->actingAs($user)->post("/lessons/{$lesson->id}/quiz", ['answers' => right($question)]);

    $this->actingAs($user)
        ->post("/lessons/{$lesson->id}/quiz", ['answers' => right($question)])
        ->assertSessionHasErrors('quiz');

    expect($quiz->attemptsBy($user)->count())->toBe(1);
});

test('attempts stop at the limit', function () {
    [$course, $lesson, $quiz, $question] = courseWithQuiz();
    $quiz->update(['max_attempts' => 2]);
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    $this->actingAs($user)->post("/lessons/{$lesson->id}/quiz", ['answers' => wrong($question)]);
    $this->actingAs($user)->post("/lessons/{$lesson->id}/quiz", ['answers' => wrong($question)]);

    $this->actingAs($user)
        ->post("/lessons/{$lesson->id}/quiz", ['answers' => right($question)])
        ->assertSessionHasErrors('quiz');

    expect($quiz->attemptsBy($user)->count())->toBe(2)
        ->and($quiz->isPassedBy($user))->toBeFalse();
});

test('the answer key never reaches the browser', function () {
    [$course, $lesson] = courseWithQuiz();
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    $this->actingAs($user)
        ->get("/learn/{$course->slug}/{$lesson->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('quiz.questions.0.options.0.is_correct'))
        ->assertDontSee('is_correct');
});

test('finishing every lesson issues a certificate', function () {
    [$course, $lesson, , $question] = courseWithQuiz(otherLessons: 1);
    $user = User::factory()->create();
    $enrollment = Enrollment::factory()->for($user)->for($course)->create();
    $plain = $course->sections()->first()->lessons()->where('type', '!=', 'quiz')->sole();

    $this->actingAs($user)->post("/lessons/{$plain->id}/complete");
    expect(Certificate::count())->toBe(0);

    $this->actingAs($user)->post("/lessons/{$lesson->id}/quiz", ['answers' => right($question)]);

    $certificate = Certificate::sole();

    expect($enrollment->fresh()->completed_at)->not->toBeNull()
        ->and($certificate->user_id)->toBe($user->id)
        ->and($certificate->course_id)->toBe($course->id)
        ->and($certificate->serial)->toStartWith('LMS-');
});

test('a certificate is issued once and survives un-completing a lesson', function () {
    [$course, $lesson, , $question] = courseWithQuiz();
    $user = User::factory()->create();
    $enrollment = Enrollment::factory()->for($user)->for($course)->create();

    $this->actingAs($user)->post("/lessons/{$lesson->id}/quiz", ['answers' => right($question)]);
    $serial = Certificate::sole()->serial;

    $enrollment->fresh()->syncCompletion();
    expect(Certificate::count())->toBe(1)->and(Certificate::sole()->serial)->toBe($serial);
});

test('serials are unique across certificates', function () {
    $serials = Certificate::factory()->count(25)->create()->pluck('serial');

    expect($serials->unique())->toHaveCount(25)
        ->and($serials->first())->toMatch('/^LMS-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/');
});

test('anyone can verify a real serial, and a fake one reports as unknown', function () {
    $certificate = Certificate::factory()->create();

    $this->get("/verify/{$certificate->serial}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('certificates/verify')
            ->where('certificate.serial', $certificate->serial)
        );

    $this->get('/verify/LMS-FAKE-FAKE-FAKE')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('certificate', null));
});

test('a certificate PDF downloads for its owner and an admin, but nobody else', function () {
    $certificate = Certificate::factory()->create();

    $this->actingAs($certificate->user)
        ->get("/certificates/{$certificate->serial}/download")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->get("/certificates/{$certificate->serial}/download")
        ->assertOk();

    $this->actingAs(User::factory()->create())
        ->get("/certificates/{$certificate->serial}/download")
        ->assertForbidden();
});
