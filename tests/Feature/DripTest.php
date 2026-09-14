<?php

use App\Enums\LessonType;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->course = Course::factory()->published()->create();
    $this->section = Section::factory()->for($this->course)->create();
    $this->later = Lesson::factory()->for($this->section)->create(['drip_days' => 7, 'content' => 'WEEK-TWO']);

    $this->user = User::factory()->create();
    $this->enrollment = Enrollment::factory()->for($this->user)->for($this->course)->create([
        'started_at' => now(),
    ]);
});

test('a dripped lesson is shut until its day arrives', function () {
    $this->actingAs($this->user)
        ->get("/learn/{$this->course->slug}/{$this->later->id}")
        ->assertForbidden();

    $this->travel(7)->days();

    $this->actingAs($this->user)
        ->get("/learn/{$this->course->slug}/{$this->later->id}")
        ->assertOk()
        ->assertSee('WEEK-TWO');
});

test('drip counts from the enrolment date, not the course date', function () {
    $late = User::factory()->create();
    Enrollment::factory()->for($late)->for($this->course)->create(['started_at' => now()->addDays(30)]);

    $this->travel(10)->days();

    // Day 10 for someone who joined on day 0: open.
    $this->actingAs($this->user)->get("/learn/{$this->course->slug}/{$this->later->id}")->assertOk();

    // Still in the future for someone whose enrolment has not started.
    $this->actingAs($late)->get("/learn/{$this->course->slug}/{$this->later->id}")->assertForbidden();
});

test('the drip closes the video route too', function () {
    Storage::fake('local');
    $this->later->update(['video_path' => UploadedFile::fake()->create('v.mp4', 8)->store('videos', 'local')]);

    $this->actingAs($this->user)->get("/lessons/{$this->later->id}/video")->assertForbidden();

    $this->travel(7)->days();

    $this->actingAs($this->user)->get("/lessons/{$this->later->id}/video")->assertOk();
});

test('a dripped lesson cannot be marked complete early', function () {
    $this->actingAs($this->user)->post("/lessons/{$this->later->id}/complete")->assertForbidden();

    $this->travel(7)->days();

    $this->actingAs($this->user)->post("/lessons/{$this->later->id}/complete")->assertRedirect();
    expect($this->user->completions()->count())->toBe(1);
});

test('a dripped quiz cannot be sat early', function () {
    $lesson = Lesson::factory()->for($this->section)->create([
        'type' => LessonType::Quiz,
        'drip_days' => 3,
    ]);
    $quiz = Quiz::factory()->create(['lesson_id' => $lesson->id]);
    $question = Question::factory()->for($quiz)->create();
    Option::factory()->for($question)->create(['is_correct' => true]);
    Option::factory()->for($question)->create(['is_correct' => false]);

    $answers = [$question->id => [$question->options()->where('is_correct', true)->value('id')]];

    $this->actingAs($this->user)
        ->post("/lessons/{$lesson->id}/quiz", ['answers' => $answers])
        ->assertForbidden();

    $this->travel(3)->days();

    $this->actingAs($this->user)
        ->post("/lessons/{$lesson->id}/quiz", ['answers' => $answers])
        ->assertRedirect();
});

test('staff are not dripped', function (string $who) {
    $user = $who === 'admin'
        ? User::factory()->create(['role' => UserRole::Admin])
        : $this->course->instructor;

    $this->actingAs($user)->get("/learn/{$this->course->slug}/{$this->later->id}")->assertOk();
})->with(['admin', 'instructor']);

test('the outline marks a dripped lesson locked and says when it opens', function () {
    $open = Lesson::factory()->for($this->section)->create(['drip_days' => 0]);

    $this->actingAs($this->user)
        ->get("/learn/{$this->course->slug}/{$open->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('outline.0.lessons.0.locked', true)
            ->whereNot('outline.0.lessons.0.unlocks_at', null)
            ->where('outline.0.lessons.1.locked', false)
        );
});

test('a dripped lesson body never reaches the browser early', function () {
    $open = Lesson::factory()->for($this->section)->create(['drip_days' => 0]);

    $this->actingAs($this->user)
        ->get("/learn/{$this->course->slug}/{$open->id}")
        ->assertOk()
        ->assertDontSee('WEEK-TWO');
});

test('drip of zero is open immediately', function () {
    $now = Lesson::factory()->for($this->section)->create(['drip_days' => 0]);

    $this->actingAs($this->user)->get("/learn/{$this->course->slug}/{$now->id}")->assertOk();
});

test('an admin can set drip days on a lesson', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->patch("/admin/lessons/{$this->later->id}", [
        'title' => $this->later->title,
        'type' => 'text',
        'drip_days' => 14,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($this->later->fresh()->drip_days)->toBe(14);

    // An empty field means no drip, not null — the column is NOT NULL.
    $this->actingAs($admin)->patch("/admin/lessons/{$this->later->id}", [
        'title' => $this->later->title,
        'type' => 'text',
        'drip_days' => '',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($this->later->fresh()->drip_days)->toBe(0);
});
