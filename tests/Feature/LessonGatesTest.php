<?php

use App\Enums\LessonType;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->course = Course::factory()->published()->create();
    $this->section = Section::factory()->for($this->course)->create();
    $this->learner = User::factory()->create();
    $this->enrollment = Enrollment::factory()->for($this->learner)->for($this->course)->create();
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

// ---------------------------------------------------------------- downloads

test('a download lesson can actually hold a file', function () {
    $lesson = Lesson::factory()->for($this->section)->create(['type' => LessonType::Download]);

    $this->actingAs($this->admin)->post("/admin/lessons/{$lesson->id}", [
        '_method' => 'patch',
        'title' => $lesson->title,
        'type' => 'download',
        'attachment' => UploadedFile::fake()->create('worksheet.pdf', 40, 'application/pdf'),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $lesson->refresh();

    expect($lesson->attachment_path)->toStartWith('attachments/')
        ->and($lesson->attachment_name)->toBe('worksheet.pdf')
        ->and(Storage::disk('local')->exists($lesson->attachment_path))->toBeTrue();
});

test('the attachment is never public and follows the lesson policy', function () {
    Storage::fake('public');

    $lesson = Lesson::factory()->for($this->section)->create([
        'type' => LessonType::Download,
        'is_preview' => false,
        'attachment_path' => UploadedFile::fake()->create('paid.pdf', 10)->store('attachments', 'local'),
        'attachment_name' => 'paid.pdf',
    ]);

    expect(Storage::disk('public')->exists($lesson->attachment_path))->toBeFalse();

    $this->get("/lessons/{$lesson->id}/attachment")->assertForbidden();
    $this->actingAs(User::factory()->create())->get("/lessons/{$lesson->id}/attachment")->assertForbidden();
    $this->actingAs($this->learner)->get("/lessons/{$lesson->id}/attachment")->assertOk();
});

test('replacing an attachment deletes the file it replaced', function () {
    $lesson = Lesson::factory()->for($this->section)->create(['type' => LessonType::Download]);

    $payload = fn ($file) => [
        '_method' => 'patch',
        'title' => $lesson->title,
        'type' => 'download',
        'attachment' => $file,
    ];

    $this->actingAs($this->admin)
        ->post("/admin/lessons/{$lesson->id}", $payload(UploadedFile::fake()->create('one.pdf', 10)));
    $first = $lesson->fresh()->attachment_path;

    $this->actingAs($this->admin)
        ->post("/admin/lessons/{$lesson->id}", $payload(UploadedFile::fake()->create('two.pdf', 10)));

    expect(Storage::disk('local')->exists($first))->toBeFalse()
        ->and(Storage::disk('local')->exists($lesson->fresh()->attachment_path))->toBeTrue();
});

test('an executable is refused as an attachment', function () {
    $lesson = Lesson::factory()->for($this->section)->create(['type' => LessonType::Download]);

    $this->actingAs($this->admin)->post("/admin/lessons/{$lesson->id}", [
        '_method' => 'patch',
        'title' => $lesson->title,
        'type' => 'download',
        'attachment' => UploadedFile::fake()->create('nasty.sh', 2, 'application/x-sh'),
    ])->assertSessionHasErrors('attachment');
});

// ------------------------------------------------------------ prerequisites

test('a lesson waiting on another is shut until that one is finished', function () {
    $first = Lesson::factory()->for($this->section)->create(['title' => 'Basics']);
    $second = Lesson::factory()->for($this->section)->create([
        'requires_lesson_id' => $first->id,
        'content' => 'ADVANCED-BODY',
    ]);

    $this->actingAs($this->learner)
        ->get("/learn/{$this->course->slug}/{$second->id}")
        ->assertForbidden();

    $this->actingAs($this->learner)->post("/lessons/{$first->id}/complete");

    $this->actingAs($this->learner)
        ->get("/learn/{$this->course->slug}/{$second->id}")
        ->assertOk()
        ->assertSee('ADVANCED-BODY');
});

test('the gate covers the video and the attachment too, not just the page', function () {
    $first = Lesson::factory()->for($this->section)->create();
    $second = Lesson::factory()->for($this->section)->create([
        'requires_lesson_id' => $first->id,
        'video_path' => UploadedFile::fake()->create('v.mp4', 8)->store('videos', 'local'),
        'attachment_path' => UploadedFile::fake()->create('w.pdf', 8)->store('attachments', 'local'),
        'attachment_name' => 'w.pdf',
    ]);

    $this->actingAs($this->learner)->get("/lessons/{$second->id}/video")->assertForbidden();
    $this->actingAs($this->learner)->get("/lessons/{$second->id}/attachment")->assertForbidden();

    $this->actingAs($this->learner)->post("/lessons/{$first->id}/complete");

    $this->actingAs($this->learner)->get("/lessons/{$second->id}/video")->assertOk();
    $this->actingAs($this->learner)->get("/lessons/{$second->id}/attachment")->assertOk();
});

test('the outline says what to finish rather than showing a bare padlock', function () {
    $first = Lesson::factory()->for($this->section)->create(['title' => 'Basics']);
    Lesson::factory()->for($this->section)->create(['requires_lesson_id' => $first->id]);

    $this->actingAs($this->learner)
        ->get("/learn/{$this->course->slug}/{$first->id}")
        ->assertInertia(fn ($page) => $page
            ->where('outline.0.lessons.1.locked', true)
            ->where('outline.0.lessons.1.requires', 'Basics')
        );
});

test('staff are not held up by prerequisites', function () {
    $first = Lesson::factory()->for($this->section)->create();
    $second = Lesson::factory()->for($this->section)->create(['requires_lesson_id' => $first->id]);

    $this->actingAs($this->course->instructor)
        ->get("/learn/{$this->course->slug}/{$second->id}")
        ->assertOk();
});

test('a lesson cannot wait on itself', function () {
    $lesson = Lesson::factory()->for($this->section)->create();

    $this->actingAs($this->admin)->patch("/admin/lessons/{$lesson->id}", [
        'title' => $lesson->title,
        'type' => 'text',
        'requires_lesson_id' => $lesson->id,
    ])->assertSessionHasErrors('requires_lesson_id');

    expect($lesson->fresh()->requires_lesson_id)->toBeNull();
});

test('two lessons cannot be made to wait on each other', function () {
    $a = Lesson::factory()->for($this->section)->create();
    $b = Lesson::factory()->for($this->section)->create(['requires_lesson_id' => $a->id]);

    // a now waiting on b would lock both forever.
    $this->actingAs($this->admin)->patch("/admin/lessons/{$a->id}", [
        'title' => $a->title,
        'type' => 'text',
        'requires_lesson_id' => $b->id,
    ])->assertSessionHasErrors('requires_lesson_id');

    expect($a->fresh()->requires_lesson_id)->toBeNull();
});

test('a longer loop is caught as well', function () {
    $a = Lesson::factory()->for($this->section)->create();
    $b = Lesson::factory()->for($this->section)->create(['requires_lesson_id' => $a->id]);
    $c = Lesson::factory()->for($this->section)->create(['requires_lesson_id' => $b->id]);

    $this->actingAs($this->admin)->patch("/admin/lessons/{$a->id}", [
        'title' => $a->title,
        'type' => 'text',
        'requires_lesson_id' => $c->id,
    ])->assertSessionHasErrors('requires_lesson_id');
});

test('a lesson cannot wait on one from another course', function () {
    $elsewhere = Lesson::factory()->create();
    $lesson = Lesson::factory()->for($this->section)->create();

    $this->actingAs($this->admin)->patch("/admin/lessons/{$lesson->id}", [
        'title' => $lesson->title,
        'type' => 'text',
        'requires_lesson_id' => $elsewhere->id,
    ])->assertSessionHasErrors('requires_lesson_id');
});

test('deleting a prerequisite unlocks what depended on it', function () {
    $first = Lesson::factory()->for($this->section)->create();
    $second = Lesson::factory()->for($this->section)->create(['requires_lesson_id' => $first->id]);

    $first->forceDelete();

    expect($second->fresh()->requires_lesson_id)->toBeNull();

    $this->actingAs($this->learner)
        ->get("/learn/{$this->course->slug}/{$second->id}")
        ->assertOk();
});
