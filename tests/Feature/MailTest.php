<?php

use App\Mail\CourseCompletedMail;
use App\Mail\OrderReceiptMail;
use App\Mail\WelcomeMail;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

test('registering sends a welcome email', function () {
    $this->post('/register', [
        'name' => 'New Person',
        'email' => 'new@lms.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Mail::assertSent(WelcomeMail::class, fn ($mail) => $mail->hasTo('new@lms.test'));
});

test('a purchase sends a receipt with the right totals', function () {
    $course = Course::factory()->published()->create(['price_cents' => 5000]);
    Lesson::factory()->for(Section::factory()->for($course))->create();
    Coupon::factory()->percent(20)->create(['code' => 'FIFTH']);
    $user = User::factory()->create();

    $this->actingAs($user)->post("/courses/{$course->slug}/purchase", ['coupon_code' => 'FIFTH']);

    Mail::assertSent(OrderReceiptMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email)
            && $mail->order->total_cents === 4000
            && $mail->order->discount_cents === 1000;
    });
});

test('a refused purchase sends no receipt', function () {
    $course = Course::factory()->published()->create(['price_cents' => 5000]);
    Lesson::factory()->for(Section::factory()->for($course))->create();

    $this->actingAs(User::factory()->create())
        ->post("/courses/{$course->slug}/purchase", ['coupon_code' => 'NOSUCH'])
        ->assertSessionHasErrors('coupon_code');

    Mail::assertNothingSent();
});

test('finishing a course emails the certificate exactly once', function () {
    $course = Course::factory()->published()->create();
    $lesson = Lesson::factory()->for(Section::factory()->for($course))->create();
    $user = User::factory()->create();
    $enrollment = Enrollment::factory()->for($user)->for($course)->create();

    $this->actingAs($user)->post("/lessons/{$lesson->id}/complete");

    Mail::assertSent(CourseCompletedMail::class, 1);

    // Re-running the completion check must not mail again.
    $enrollment->fresh()->syncCompletion();

    Mail::assertSent(CourseCompletedMail::class, 1);
});

test('a half-finished course sends nothing', function () {
    $course = Course::factory()->published()->create();
    $section = Section::factory()->for($course)->create();
    $lessons = Lesson::factory()->count(2)->for($section)->create();
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    $this->actingAs($user)->post("/lessons/{$lessons[0]->id}/complete");

    Mail::assertNotSent(CourseCompletedMail::class);
});
