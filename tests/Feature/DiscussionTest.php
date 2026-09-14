<?php

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->course = Course::factory()->published()->create();
    $this->section = Section::factory()->for($this->course)->create();
    $this->lesson = Lesson::factory()->for($this->section)->create(['is_preview' => false]);
    $this->preview = Lesson::factory()->for($this->section)->create(['is_preview' => true]);

    $this->learner = User::factory()->create();
    Enrollment::factory()->for($this->learner)->for($this->course)->create();
});

test('an enrolled learner asks a question', function () {
    $this->actingAs($this->learner)
        ->post("/lessons/{$this->lesson->id}/comments", ['body' => 'Why does this fail?'])
        ->assertRedirect();

    $comment = LessonComment::sole();

    expect($comment->body)->toBe('Why does this fail?')
        ->and($comment->user_id)->toBe($this->learner->id)
        ->and($comment->parent_id)->toBeNull()
        ->and($comment->resolved_at)->toBeNull();
});

test('someone not enrolled cannot ask, even on a preview lesson', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post("/lessons/{$this->preview->id}/comments", ['body' => 'hello'])
        ->assertForbidden();

    expect(LessonComment::count())->toBe(0);
});

test('a guest is sent to log in', function () {
    $this->post("/lessons/{$this->lesson->id}/comments", ['body' => 'hi'])->assertRedirect('/login');
});

test('an empty question is refused', function () {
    $this->actingAs($this->learner)
        ->post("/lessons/{$this->lesson->id}/comments", ['body' => ''])
        ->assertSessionHasErrors('body');
});

test('a dripped lesson cannot be asked about before it opens', function () {
    $later = Lesson::factory()->for($this->section)->create(['drip_days' => 10]);

    $this->actingAs($this->learner)
        ->post("/lessons/{$later->id}/comments", ['body' => 'early'])
        ->assertForbidden();

    $this->travel(11)->days();

    $this->actingAs($this->learner)
        ->post("/lessons/{$later->id}/comments", ['body' => 'on time'])
        ->assertRedirect();
});

test('replies attach to their question', function () {
    $question = LessonComment::factory()->for($this->lesson)->create();

    $this->actingAs($this->learner)
        ->post("/lessons/{$this->lesson->id}/comments", [
            'body' => 'Try clearing the cache.',
            'parent_id' => $question->id,
        ])
        ->assertRedirect();

    expect($question->replies()->sole()->body)->toBe('Try clearing the cache.');
});

test('a reply cannot be smuggled onto another lesson', function () {
    $elsewhere = LessonComment::factory()->create();

    $this->actingAs($this->learner)
        ->post("/lessons/{$this->lesson->id}/comments", [
            'body' => 'wrong place',
            'parent_id' => $elsewhere->id,
        ])
        ->assertSessionHasErrors('body');

    expect($elsewhere->replies()->count())->toBe(0);
});

test('threads stay one level deep', function () {
    $question = LessonComment::factory()->for($this->lesson)->create();
    $reply = LessonComment::factory()->for($this->lesson)->create(['parent_id' => $question->id]);

    $this->actingAs($this->learner)
        ->post("/lessons/{$this->lesson->id}/comments", [
            'body' => 'reply to a reply',
            'parent_id' => $reply->id,
        ])
        ->assertSessionHasErrors('body');
});

test('the asker and the course owner can mark a question answered; others cannot', function () {
    $question = LessonComment::factory()->for($this->lesson)->for($this->learner, 'author')->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->patch("/comments/{$question->id}/resolve")->assertForbidden();

    $this->actingAs($this->learner)->patch("/comments/{$question->id}/resolve")->assertRedirect();
    expect($question->fresh()->resolved_at)->not->toBeNull();

    // Toggles back off.
    $this->actingAs($this->course->instructor)->patch("/comments/{$question->id}/resolve")->assertRedirect();
    expect($question->fresh()->resolved_at)->toBeNull();
});

test('a reply cannot be marked answered', function () {
    $question = LessonComment::factory()->for($this->lesson)->create();
    $reply = LessonComment::factory()->for($this->lesson)->for($this->learner, 'author')->create([
        'parent_id' => $question->id,
    ]);

    $this->actingAs($this->learner)->patch("/comments/{$reply->id}/resolve")->assertForbidden();
});

test('authors delete their own; the course owner deletes any; strangers delete none', function () {
    $mine = LessonComment::factory()->for($this->lesson)->for($this->learner, 'author')->create();
    $theirs = LessonComment::factory()->for($this->lesson)->create();

    $this->actingAs($this->learner)->delete("/comments/{$theirs->id}")->assertForbidden();
    $this->actingAs($this->learner)->delete("/comments/{$mine->id}")->assertRedirect();

    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->delete("/comments/{$theirs->id}")
        ->assertRedirect();

    expect(LessonComment::count())->toBe(0);
});

test('deleting a question takes its replies with it', function () {
    $question = LessonComment::factory()->for($this->lesson)->for($this->learner, 'author')->create();
    LessonComment::factory()->count(2)->for($this->lesson)->create(['parent_id' => $question->id]);

    $this->actingAs($this->learner)->delete("/comments/{$question->id}")->assertRedirect();

    expect(LessonComment::count())->toBe(0);
});

test('the thread renders with replies nested and staff marked', function () {
    $question = LessonComment::factory()->for($this->lesson)->for($this->learner, 'author')
        ->create(['body' => 'A learner question']);
    LessonComment::factory()->for($this->lesson)->for($this->course->instructor, 'author')
        ->create(['parent_id' => $question->id, 'body' => 'An instructor answer']);

    $this->actingAs($this->learner)
        ->get("/learn/{$this->course->slug}/{$this->lesson->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('discussion', 1)
            ->where('discussion.0.body', 'A learner question')
            ->where('discussion.0.from_staff', false)
            ->where('discussion.0.replies.0.body', 'An instructor answer')
            ->where('discussion.0.replies.0.from_staff', true)
            ->where('can_comment', true)
        );
});

test('a guest reading a preview sees the thread but cannot post', function () {
    LessonComment::factory()->for($this->preview)->create(['body' => 'Visible question']);

    $this->get("/learn/{$this->course->slug}/{$this->preview->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('discussion', 1)
            ->where('can_comment', false)
        );
});
