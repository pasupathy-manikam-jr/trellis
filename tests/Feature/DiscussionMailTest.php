<?php

use App\Mail\LessonQuestionMail;
use App\Mail\LessonReplyMail;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->course = Course::factory()->published()->create();
    $this->lesson = Lesson::factory()->for(Section::factory()->for($this->course))->create();

    $this->learner = User::factory()->create();
    Enrollment::factory()->for($this->learner)->for($this->course)->create();
});

test('a new question reaches whoever owns the course', function () {
    $this->actingAs($this->learner)
        ->post("/lessons/{$this->lesson->id}/comments", ['body' => 'Why does this fail?']);

    Mail::assertSent(LessonQuestionMail::class, fn ($mail) => $mail->hasTo($this->course->instructor->email));
});

test('replying to the learner reaches the learner, not the course owner', function () {
    $question = LessonComment::factory()->for($this->lesson)->for($this->learner, 'author')->create();

    $this->actingAs($this->course->instructor)
        ->post("/lessons/{$this->lesson->id}/comments", [
            'body' => 'Clear the cache.',
            'parent_id' => $question->id,
        ]);

    Mail::assertSent(LessonReplyMail::class, fn ($mail) => $mail->hasTo($this->learner->email));
    Mail::assertNotSent(LessonQuestionMail::class);
});

test('nobody is mailed about their own writing', function () {
    // The course owner asking their own question.
    $this->actingAs($this->course->instructor)
        ->post("/lessons/{$this->lesson->id}/comments", ['body' => 'A note to self']);

    Mail::assertNothingSent();

    // The asker replying to themselves.
    $own = LessonComment::factory()->for($this->lesson)->for($this->learner, 'author')->create();

    $this->actingAs($this->learner)
        ->post("/lessons/{$this->lesson->id}/comments", [
            'body' => 'Never mind, solved it.',
            'parent_id' => $own->id,
        ]);

    Mail::assertNothingSent();
});

test('a refused comment sends nothing', function () {
    $this->actingAs($this->learner)
        ->post("/lessons/{$this->lesson->id}/comments", ['body' => ''])
        ->assertSessionHasErrors('body');

    Mail::assertNothingSent();
});

test('the question email replies to the learner who asked', function () {
    $this->actingAs($this->learner)
        ->post("/lessons/{$this->lesson->id}/comments", ['body' => 'A question']);

    Mail::assertSent(LessonQuestionMail::class, function ($mail) {
        return $mail->hasReplyTo($this->learner->email);
    });
});
