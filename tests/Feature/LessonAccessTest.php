<?php

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function lessonOn(Course $course, array $attributes = []): Lesson
{
    return Lesson::factory()
        ->for(Section::factory()->for($course))
        ->create($attributes);
}

beforeEach(function () {
    $this->course = Course::factory()->published()->create();
    $this->gated = lessonOn($this->course, ['is_preview' => false, 'content' => 'PAID-BODY']);
    $this->preview = lessonOn($this->course, ['is_preview' => true]);
});

test('a guest may read a preview lesson', function () {
    $this->get("/learn/{$this->course->slug}/{$this->preview->id}")->assertOk();
});

test('a guest is refused a gated lesson', function () {
    $this->get("/learn/{$this->course->slug}/{$this->gated->id}")->assertForbidden();
});

test('a signed-in stranger is refused a gated lesson', function () {
    $this->actingAs(User::factory()->create())
        ->get("/learn/{$this->course->slug}/{$this->gated->id}")
        ->assertForbidden();
});

test('an enrolled learner may read a gated lesson', function () {
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($this->course)->create();

    $this->actingAs($user)
        ->get("/learn/{$this->course->slug}/{$this->gated->id}")
        ->assertOk()
        ->assertSee('PAID-BODY');
});

test('an expired enrolment stops working', function () {
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($this->course)->expired()->create();

    $this->actingAs($user)
        ->get("/learn/{$this->course->slug}/{$this->gated->id}")
        ->assertForbidden();
});

test('staff may read anything', function (string $who) {
    $user = $who === 'admin'
        ? User::factory()->create(['role' => UserRole::Admin])
        : $this->course->instructor;

    $this->actingAs($user)
        ->get("/learn/{$this->course->slug}/{$this->gated->id}")
        ->assertOk();
})->with(['admin', 'instructor']);

test('an unpublished course is closed even to its preview lessons', function () {
    $draft = Course::factory()->create();
    $preview = lessonOn($draft, ['is_preview' => true]);

    $this->get("/learn/{$draft->slug}/{$preview->id}")->assertForbidden();
});

test('a lesson cannot be reached through another course url', function () {
    $other = Course::factory()->published()->create();

    $this->get("/learn/{$other->slug}/{$this->preview->id}")->assertNotFound();
});

test('the outline never carries bodies of lessons the viewer cannot open', function () {
    $this->get("/learn/{$this->course->slug}/{$this->preview->id}")
        ->assertOk()
        ->assertDontSee('PAID-BODY');
});

test('the video stream enforces the same rule as the lesson page', function () {
    Storage::fake('local');
    $this->gated->update(['video_path' => UploadedFile::fake()->create('v.mp4', 16)->store('videos', 'local')]);

    $this->get("/lessons/{$this->gated->id}/video")->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get("/lessons/{$this->gated->id}/video")
        ->assertForbidden();

    $enrolled = User::factory()->create();
    Enrollment::factory()->for($enrolled)->for($this->course)->create();

    $this->actingAs($enrolled)
        ->get("/lessons/{$this->gated->id}/video")
        ->assertOk()
        ->assertHeader('accept-ranges', 'bytes');
});

test('uploaded video is stored off the web-reachable disk', function () {
    Storage::fake('local');
    Storage::fake('public');

    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->post("/admin/sections/{$this->gated->section_id}/lessons", [
        'title' => 'With video',
        'type' => 'video',
        'video' => UploadedFile::fake()->create('lesson.mp4', 32, 'video/mp4'),
    ])->assertRedirect();

    $lesson = Lesson::where('title', 'With video')->sole();

    expect($lesson->video_path)->toStartWith('videos/')
        ->and(Storage::disk('local')->exists($lesson->video_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($lesson->video_path))->toBeFalse();
});

test('editing a lesson with an upload works through spoofed PATCH', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    // What the browser actually sends: multipart POST with _method=patch and
    // booleans flattened to "1"/"0".
    $this->actingAs($admin)->post("/admin/lessons/{$this->gated->id}", [
        '_method' => 'patch',
        'title' => 'Renamed with video',
        'type' => 'video',
        'is_preview' => '1',
        'duration_sec' => '90',
        'video' => UploadedFile::fake()->create('clip.mp4', 8, 'video/mp4'),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $lesson = $this->gated->fresh();

    expect($lesson->title)->toBe('Renamed with video')
        ->and($lesson->is_preview)->toBeTrue()
        ->and($lesson->duration_sec)->toBe(90)
        ->and($lesson->video_path)->toStartWith('videos/');
});

test('replacing a video deletes the file it replaced', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $first = UploadedFile::fake()->create('a.mp4', 8, 'video/mp4')->store('videos', 'local');
    $this->gated->update(['video_path' => $first]);

    $this->actingAs($admin)->post("/admin/lessons/{$this->gated->id}", [
        '_method' => 'patch',
        'title' => $this->gated->title,
        'type' => 'video',
        'video' => UploadedFile::fake()->create('b.mp4', 8, 'video/mp4'),
    ])->assertRedirect();

    expect(Storage::disk('local')->exists($first))->toBeFalse()
        ->and(Storage::disk('local')->exists($this->gated->fresh()->video_path))->toBeTrue();
});
