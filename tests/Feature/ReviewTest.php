<?php

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;

beforeEach(function () {
    $this->course = Course::factory()->published()->create();
    $this->user = User::factory()->create();
    Enrollment::factory()->for($this->user)->for($this->course)->create();
});

test('an enrolled learner can review the course', function () {
    $this->actingAs($this->user)
        ->post("/courses/{$this->course->slug}/reviews", ['rating' => 5, 'body' => 'Clear and practical.'])
        ->assertRedirect();

    $review = Review::sole();

    expect($review->rating)->toBe(5)
        ->and($review->user_id)->toBe($this->user->id)
        ->and($review->course_id)->toBe($this->course->id);
});

test('someone who never enrolled cannot review', function () {
    $this->actingAs(User::factory()->create())
        ->post("/courses/{$this->course->slug}/reviews", ['rating' => 1])
        ->assertForbidden();

    expect(Review::count())->toBe(0);
});

test('a guest cannot review', function () {
    $this->post("/courses/{$this->course->slug}/reviews", ['rating' => 5])->assertRedirect('/login');
});

test('reviewing twice edits the existing review rather than adding one', function () {
    $this->actingAs($this->user)->post("/courses/{$this->course->slug}/reviews", ['rating' => 3]);
    $this->actingAs($this->user)->post("/courses/{$this->course->slug}/reviews", ['rating' => 5, 'body' => 'Better than I thought.']);

    expect(Review::count())->toBe(1)
        ->and(Review::sole()->rating)->toBe(5)
        ->and(Review::sole()->body)->toBe('Better than I thought.');
});

test('ratings outside one to five are refused', function (int $rating) {
    $this->actingAs($this->user)
        ->post("/courses/{$this->course->slug}/reviews", ['rating' => $rating])
        ->assertSessionHasErrors('rating');
})->with([0, 6, 99]);

test('the course page shows the average and the count', function () {
    Review::factory()->for($this->course)->create(['rating' => 5]);
    Review::factory()->for($this->course)->create(['rating' => 4]);
    Review::factory()->for($this->course)->create(['rating' => 4]);

    $this->get("/courses/{$this->course->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('reviews.average', 4.3)
            ->where('reviews.count', 3)
        );
});

test('an unrated course has no average, rather than an average of zero', function () {
    $this->get("/courses/{$this->course->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('reviews.average', null)->where('reviews.count', 0));
});

test('an author deletes their own review; an admin can too; a stranger cannot', function () {
    $review = Review::factory()->for($this->course)->for($this->user)->create();

    $this->actingAs(User::factory()->create())
        ->delete("/reviews/{$review->id}")
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->delete("/reviews/{$review->id}")
        ->assertRedirect();

    expect(Review::count())->toBe(0);
});
