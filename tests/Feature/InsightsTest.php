<?php

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;

test('insights aggregate revenue, completion and rating per course', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $course = Course::factory()->published()->create(['price_cents' => 5000]);

    Order::factory()->for($course)->create(['total_cents' => 5000]);
    Order::factory()->for($course)->create(['total_cents' => 4000, 'status' => OrderStatus::Refunded]);

    Enrollment::factory()->for($course)->create(['completed_at' => now()]);
    Enrollment::factory()->for($course)->create();
    Review::factory()->for($course)->create(['rating' => 4]);
    Review::factory()->for($course)->create(['rating' => 5]);

    $this->actingAs($admin)
        ->get('/admin/insights')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/insights')
            // Refunded orders are excluded from revenue.
            ->where('courses.0.revenue_cents', 5000)
            ->where('courses.0.enrollments', 2)
            ->where('courses.0.completed', 1)
            ->where('courses.0.completion_rate', 50)
            ->where('courses.0.rating', 4.5)
            ->where('totals.revenue_cents', 5000)
        );
});

test('a course with no enrolments has no completion rate, not zero percent', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    Course::factory()->published()->create();

    $this->actingAs($admin)
        ->get('/admin/insights')
        ->assertInertia(fn ($page) => $page
            ->where('courses.0.completion_rate', null)
            ->where('courses.0.rating', null)
        );
});

test('guests are sent to log in', function () {
    $this->get('/admin/insights')->assertRedirect('/login');
});

test('a signed-in learner is refused', function () {
    $this->actingAs(User::factory()->create())->get('/admin/insights')->assertForbidden();
});
