<?php

use App\Enums\EnrollmentSource;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\Section;
use App\Models\User;

function sellableCourse(int $priceCents = 4900): Course
{
    $course = Course::factory()->published()->create(['price_cents' => $priceCents]);
    Lesson::factory()->for(Section::factory()->for($course))->create();

    return $course;
}

test('buying a course records the order and grants access once the gateway confirms', function () {
    $course = sellableCourse(4900);
    $user = User::factory()->create();

    $order = buyCourse($this, $course, $user);

    expect($order->subtotal_cents)->toBe(4900)
        ->and($order->discount_cents)->toBe(0)
        ->and($order->total_cents)->toBe(4900)
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->paid_at)->not->toBeNull()
        ->and(Enrollment::sole()->source)->toBe(EnrollmentSource::Purchase);
});

test('a free course takes the same path and is recorded as free', function () {
    $course = sellableCourse(0);
    $user = User::factory()->create();

    buyCourse($this, $course, $user);

    expect(Order::sole()->total_cents)->toBe(0)
        ->and(Enrollment::sole()->source)->toBe(EnrollmentSource::Free);
});

test('a percentage coupon discounts the order', function () {
    $course = sellableCourse(5000);
    Coupon::factory()->percent(25)->create(['code' => 'QUARTER']);

    buyCourse($this, $course, User::factory()->create(), ['coupon_code' => 'QUARTER']);

    $order = Order::sole();

    expect($order->discount_cents)->toBe(1250)
        ->and($order->total_cents)->toBe(3750)
        ->and($order->coupon_id)->not->toBeNull();
});

test('a fixed-amount coupon discounts the order', function () {
    $course = sellableCourse(4900);
    Coupon::factory()->amount(1000)->create(['code' => 'TENOFF']);

    buyCourse($this, $course, User::factory()->create(), ['coupon_code' => 'TENOFF']);

    expect(Order::sole()->total_cents)->toBe(3900);
});

test('a discount never exceeds the price', function () {
    $course = sellableCourse(1000);
    Coupon::factory()->amount(999999)->create(['code' => 'HUGE']);

    buyCourse($this, $course, User::factory()->create(), ['coupon_code' => 'HUGE']);

    $order = Order::sole();

    expect($order->discount_cents)->toBe(1000)
        ->and($order->total_cents)->toBe(0)
        ->and(Enrollment::sole()->source)->toBe(EnrollmentSource::Free);
});

test('percentage rounding is half-up and never leaves a negative total', function () {
    $coupon = Coupon::factory()->percent(50)->make();

    expect($coupon->discountFor(4999))->toBe(2500)
        ->and($coupon->discountFor(4998))->toBe(2499)
        ->and($coupon->discountFor(1))->toBe(1)
        ->and($coupon->discountFor(0))->toBe(0);
});

test('an unknown, expired or exhausted coupon is rejected and nothing is charged', function (string $code, callable $make) {
    $course = sellableCourse();
    $make();

    $this->actingAs(User::factory()->create())
        ->post("/courses/{$course->slug}/purchase", ['coupon_code' => $code])
        ->assertSessionHasErrors('coupon_code');

    expect(Order::count())->toBe(0)
        ->and(Enrollment::count())->toBe(0);
})->with([
    'unknown' => ['NOPE', fn () => null],
    'expired' => ['OLD', fn () => Coupon::factory()->expired()->create(['code' => 'OLD'])],
    'exhausted' => ['GONE', fn () => Coupon::factory()->exhausted()->create(['code' => 'GONE'])],
]);

test('redemptions are counted and stop at the limit', function () {
    $course = sellableCourse();
    Coupon::factory()->percent(10)->create(['code' => 'TWICE', 'max_redemptions' => 2]);

    foreach (range(1, 2) as $i) {
        buyCourse($this, $course, User::factory()->create(), ['coupon_code' => 'TWICE']);
    }

    expect(Coupon::sole()->redeemed_count)->toBe(2);

    $this->actingAs(User::factory()->create())
        ->post("/courses/{$course->slug}/purchase", ['coupon_code' => 'TWICE'])
        ->assertSessionHasErrors('coupon_code');

    expect(Order::count())->toBe(2);
});

test('buying the same course twice is refused once the first one lands', function () {
    $course = sellableCourse();
    $user = User::factory()->create();

    buyCourse($this, $course, $user);
    $this->actingAs($user)->post("/courses/{$course->slug}/purchase")->assertForbidden();

    expect(Order::count())->toBe(1)
        ->and(Enrollment::count())->toBe(1);
});

test('a draft course cannot be bought', function () {
    $course = Course::factory()->create(['price_cents' => 1000]);

    $this->actingAs(User::factory()->create())
        ->post("/courses/{$course->slug}/purchase")
        ->assertForbidden();
});

test('guests are sent to log in', function () {
    $course = sellableCourse();

    $this->post("/courses/{$course->slug}/purchase")->assertRedirect('/login');
});

test('a refund revokes access and frees the redemption', function () {
    $course = sellableCourse();
    $user = User::factory()->create();
    Coupon::factory()->percent(10)->create(['code' => 'BACK', 'max_redemptions' => 1]);

    buyCourse($this, $course, $user, ['coupon_code' => 'BACK']);

    $order = Order::sole();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund")->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and($order->fresh()->refunded_at)->not->toBeNull()
        ->and(Enrollment::count())->toBe(0)
        ->and(Coupon::sole()->redeemed_count)->toBe(0);
});

test('refunding twice is refused', function () {
    $order = Order::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund")->assertRedirect();
    $this->actingAs($admin)->post("/admin/orders/{$order->id}/refund")->assertStatus(409);
});

test('only admins may refund or manage coupons', function () {
    $order = Order::factory()->create();
    $student = User::factory()->create();

    $this->actingAs($student)->post("/admin/orders/{$order->id}/refund")->assertForbidden();
    $this->actingAs($student)->get('/admin/coupons')->assertForbidden();
    $this->actingAs($student)->post('/admin/coupons', ['code' => 'X'])->assertForbidden();
});

test('a coupon must be either a percentage or an amount, not neither', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->post('/admin/coupons', ['code' => 'BAD', 'kind' => 'percent'])
        ->assertSessionHasErrors('percent_off');

    $this->actingAs($admin)
        ->post('/admin/coupons', ['code' => 'BAD', 'kind' => 'amount'])
        ->assertSessionHasErrors('amount_off_cents');

    expect(Coupon::count())->toBe(0);
});

test('a learner sees their own orders and nobody else\'s', function () {
    $mine = Order::factory()->create();
    Order::factory()->create();

    $this->actingAs($mine->user)
        ->get('/orders')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('orders/index')->has('orders', 1));
});
