<?php

use App\Enums\LessonType;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\GradeItem;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->course = Course::factory()->published()->create();
    $this->section = Section::factory()->for($this->course)->create();
    $this->lesson = Lesson::factory()->for($this->section)->create(['type' => LessonType::Assignment]);
    $this->assignment = Assignment::factory()->for($this->lesson)->create(['points' => 50]);

    $this->learner = User::factory()->create();
    $this->enrollment = Enrollment::factory()->for($this->learner)->for($this->course)->create();
});

test('an enrolled learner hands work in', function () {
    $this->actingAs($this->learner)
        ->post("/assignments/{$this->assignment->id}/submissions", ['body' => 'Here is my answer.'])
        ->assertRedirect();

    expect(Submission::sole()->body)->toBe('Here is my answer.')
        ->and(Submission::sole()->user_id)->toBe($this->learner->id);
});

test('handing in completes the lesson, whatever the work is worth', function () {
    $this->actingAs($this->learner)
        ->post("/assignments/{$this->assignment->id}/submissions", ['body' => 'Done.']);

    expect($this->learner->completions()->where('lesson_id', $this->lesson->id)->exists())->toBeTrue();
});

test('an empty submission is refused', function () {
    $this->actingAs($this->learner)
        ->post("/assignments/{$this->assignment->id}/submissions", ['body' => ''])
        ->assertSessionHasErrors('body');

    expect(Submission::count())->toBe(0);
});

test('a file alone is enough', function () {
    $this->actingAs($this->learner)
        ->post("/assignments/{$this->assignment->id}/submissions", [
            'file' => UploadedFile::fake()->create('essay.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $submission = Submission::sole();

    expect($submission->file_path)->toStartWith('submissions/')
        ->and($submission->file_name)->toBe('essay.pdf')
        ->and(Storage::disk('local')->exists($submission->file_path))->toBeTrue();
});

test('coursework is stored off the web-reachable disk', function () {
    Storage::fake('public');

    $this->actingAs($this->learner)
        ->post("/assignments/{$this->assignment->id}/submissions", [
            'file' => UploadedFile::fake()->create('essay.pdf', 10, 'application/pdf'),
        ]);

    expect(Storage::disk('public')->exists(Submission::sole()->file_path))->toBeFalse();
});

test('resubmitting replaces the work and deletes the old file', function () {
    $this->actingAs($this->learner)->post("/assignments/{$this->assignment->id}/submissions", [
        'body' => 'First go',
        'file' => UploadedFile::fake()->create('one.pdf', 10, 'application/pdf'),
    ]);

    $first = Submission::sole()->file_path;

    $this->actingAs($this->learner)->post("/assignments/{$this->assignment->id}/submissions", [
        'body' => 'Second go',
        'file' => UploadedFile::fake()->create('two.pdf', 10, 'application/pdf'),
    ]);

    expect(Submission::count())->toBe(1)
        ->and(Submission::sole()->body)->toBe('Second go')
        ->and(Storage::disk('local')->exists($first))->toBeFalse();
});

test('work cannot be changed once it has been marked', function () {
    $this->actingAs($this->learner)
        ->post("/assignments/{$this->assignment->id}/submissions", ['body' => 'Final answer']);

    GradeItem::where('lesson_id', $this->lesson->id)->sole()->award($this->learner, 45);

    $this->actingAs($this->learner)
        ->post("/assignments/{$this->assignment->id}/submissions", ['body' => 'Actually, wait'])
        ->assertSessionHasErrors('body');

    expect(Submission::sole()->body)->toBe('Final answer');
});

test('someone not enrolled cannot hand anything in', function () {
    $this->actingAs(User::factory()->create())
        ->post("/assignments/{$this->assignment->id}/submissions", ['body' => 'hello'])
        ->assertForbidden();

    expect(Submission::count())->toBe(0);
});

test('a dripped assignment cannot be submitted early', function () {
    $this->lesson->update(['drip_days' => 14]);

    $this->actingAs($this->learner)
        ->post("/assignments/{$this->assignment->id}/submissions", ['body' => 'early'])
        ->assertForbidden();

    $this->travel(15)->days();

    $this->actingAs($this->learner)
        ->post("/assignments/{$this->assignment->id}/submissions", ['body' => 'on time'])
        ->assertRedirect();
});

test('the author and the course owner can download the file; nobody else can', function () {
    $this->actingAs($this->learner)->post("/assignments/{$this->assignment->id}/submissions", [
        'file' => UploadedFile::fake()->create('work.pdf', 10, 'application/pdf'),
    ]);

    $submission = Submission::sole();

    $this->actingAs($this->learner)->get("/submissions/{$submission->id}/file")->assertOk();
    $this->actingAs($this->course->instructor)->get("/submissions/{$submission->id}/file")->assertOk();
    $this->actingAs(User::factory()->create())->get("/submissions/{$submission->id}/file")->assertForbidden();
});

test('late work is accepted and flagged rather than refused', function () {
    $this->assignment->update(['due_days' => 3]);
    $this->travel(10)->days();

    $this->actingAs($this->learner)
        ->post("/assignments/{$this->assignment->id}/submissions", ['body' => 'sorry'])
        ->assertRedirect();

    expect(Submission::sole()->isLate($this->enrollment))->toBeTrue();
});

test('a due date counts from the learner own enrolment', function () {
    $this->assignment->update(['due_days' => 7]);

    $late = User::factory()->create();
    $lateEnrollment = Enrollment::factory()->for($late)->for($this->course)
        ->create(['started_at' => now()->addDays(30)]);

    expect($this->assignment->dueFor($this->enrollment)->toDateString())
        ->toBe(now()->addDays(7)->toDateString())
        ->and($this->assignment->dueFor($lateEnrollment)->toDateString())
        ->toBe(now()->addDays(37)->toDateString());
});
