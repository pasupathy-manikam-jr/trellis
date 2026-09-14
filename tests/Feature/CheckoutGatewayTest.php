<?php

use App\Enums\EnrollmentSource;
use App\Enums\OrderStatus;
use App\Mail\OrderReceiptMail;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;

function sellable(int $cents = 4900): Course
{
    $course = Course::factory()->published()->create(['price_cents' => $cents]);
    Lesson::factory()->for(Section::factory()->for($course))->create();

    return $course;
}

/** What the gateway posts back to us, out of band. */
function callback(string $reference, string $outcome = 'paid', ?string $secret = null): array
{
    return [
        'reference' => $reference,
        'outcome' => $outcome,
        'secret' => $secret ?? config('services.fake_gateway.secret'),
    ];
}

beforeEach(function () {
    Mail::fake();
    $this->buyer = User::factory()->create();
});

test('starting checkout creates a pending order and grants nothing yet', function () {
    $course = sellable();

    $this->actingAs($this->buyer)
        ->post("/courses/{$course->slug}/purchase")
        ->assertRedirect();

    $order = Order::sole();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->reference)->toStartWith('chk_')
        ->and($order->paid_at)->toBeNull()
        ->and(Enrollment::count())->toBe(0);

    Mail::assertNothingSent();
});

test('the callback is what grants access, not the redirect', function () {
    $course = sellable();
    $this->actingAs($this->buyer)->post("/courses/{$course->slug}/purchase");
    $order = Order::sole();

    $this->postJson('/payments/callback', callback($order->reference))
        ->assertOk()
        ->assertJson(['status' => 'confirmed']);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->paid_at)->not->toBeNull()
        ->and(Enrollment::sole()->user_id)->toBe($this->buyer->id)
        ->and(Enrollment::sole()->source)->toBe(EnrollmentSource::Purchase);

    Mail::assertSent(OrderReceiptMail::class, 1);
});

test('a replayed callback enrols once and receipts once', function () {
    $course = sellable();
    $this->actingAs($this->buyer)->post("/courses/{$course->slug}/purchase");
    $reference = Order::sole()->reference;

    foreach (range(1, 4) as $i) {
        $this->postJson('/payments/callback', callback($reference))->assertOk();
    }

    expect(Enrollment::count())->toBe(1)
        ->and(Order::where('status', OrderStatus::Paid)->count())->toBe(1);

    Mail::assertSent(OrderReceiptMail::class, 1);
});

test('a duplicate callback reports that it was already handled', function () {
    $course = sellable();
    $this->actingAs($this->buyer)->post("/courses/{$course->slug}/purchase");
    $reference = Order::sole()->reference;

    $this->postJson('/payments/callback', callback($reference))->assertJson(['status' => 'confirmed']);
    $this->postJson('/payments/callback', callback($reference))->assertJson(['status' => 'already_handled']);
});

test('a declined callback records the failure and grants nothing', function () {
    $course = sellable();
    $this->actingAs($this->buyer)->post("/courses/{$course->slug}/purchase");
    $order = Order::sole();

    $this->postJson('/payments/callback', callback($order->reference, 'declined'))
        ->assertJson(['status' => 'failed']);

    expect($order->fresh()->status)->toBe(OrderStatus::Failed)
        ->and($order->fresh()->failure_reason)->toBe('Card declined')
        ->and(Enrollment::count())->toBe(0);

    Mail::assertNothingSent();
});

test('a late callback on a failed order cannot resurrect it', function () {
    $course = sellable();
    $this->actingAs($this->buyer)->post("/courses/{$course->slug}/purchase");
    $reference = Order::sole()->reference;

    $this->postJson('/payments/callback', callback($reference, 'declined'));
    $this->postJson('/payments/callback', callback($reference, 'paid'))
        ->assertJson(['status' => 'already_handled']);

    expect(Order::sole()->status)->toBe(OrderStatus::Failed)
        ->and(Enrollment::count())->toBe(0);
});

test('a forged callback is refused', function () {
    $course = sellable();
    $this->actingAs($this->buyer)->post("/courses/{$course->slug}/purchase");

    $this->postJson('/payments/callback', callback(Order::sole()->reference, 'paid', 'guessed'))
        ->assertForbidden();

    expect(Enrollment::count())->toBe(0);
});

test('an unknown reference is acknowledged so the gateway stops retrying', function () {
    $this->postJson('/payments/callback', callback('chk_nothing'))
        ->assertOk()
        ->assertJson(['status' => 'ignored']);
});

test('an abandoned checkout leaves the order pending and grants nothing', function () {
    $course = sellable();
    $this->actingAs($this->buyer)->post("/courses/{$course->slug}/purchase");
    $order = Order::sole();

    $this->actingAs($this->buyer)
        ->post("/checkout/{$order->reference}", ['outcome' => 'abandoned'])
        ->assertRedirect("/courses/{$course->slug}");

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and(Enrollment::count())->toBe(0);
});

test('paying on the gateway page enrols and lands on the player', function () {
    $course = sellable();
    $this->actingAs($this->buyer)->post("/courses/{$course->slug}/purchase");
    $order = Order::sole();

    $this->actingAs($this->buyer)
        ->post("/checkout/{$order->reference}", ['outcome' => 'paid'])
        ->assertRedirect("/learn/{$course->slug}");

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and(Enrollment::count())->toBe(1);
});

test('a free course skips the gateway entirely', function () {
    $course = sellable(0);

    $this->actingAs($this->buyer)
        ->post("/courses/{$course->slug}/purchase")
        ->assertRedirect("/learn/{$course->slug}");

    expect(Order::sole()->status)->toBe(OrderStatus::Paid)
        ->and(Enrollment::sole()->source)->toBe(EnrollmentSource::Free);

    Mail::assertSent(OrderReceiptMail::class, 1);
});

test('a coupon taking the price to zero also skips the gateway', function () {
    $course = sellable(5000);
    Coupon::factory()->percent(100)->create(['code' => 'ONHOUSE']);

    $this->actingAs($this->buyer)
        ->post("/courses/{$course->slug}/purchase", ['coupon_code' => 'ONHOUSE'])
        ->assertRedirect("/learn/{$course->slug}");

    expect(Order::sole()->total_cents)->toBe(0)
        ->and(Enrollment::sole()->source)->toBe(EnrollmentSource::Free);
});

test('a coupon is only spent when the payment confirms', function () {
    $course = sellable();
    Coupon::factory()->percent(10)->create(['code' => 'TENTH']);

    $this->actingAs($this->buyer)->post("/courses/{$course->slug}/purchase", ['coupon_code' => 'TENTH']);

    expect(Coupon::sole()->redeemed_count)->toBe(0);

    $this->postJson('/payments/callback', callback(Order::sole()->reference));

    expect(Coupon::sole()->redeemed_count)->toBe(1);
});

test('an abandoned checkout does not burn the coupon', function () {
    $course = sellable();
    Coupon::factory()->percent(10)->create(['code' => 'KEPT', 'max_redemptions' => 1]);

    $this->actingAs($this->buyer)->post("/courses/{$course->slug}/purchase", ['coupon_code' => 'KEPT']);

    expect(Coupon::sole()->redeemed_count)->toBe(0)
        ->and(Coupon::sole()->isRedeemable())->toBeTrue();
});

test('nobody can open or drive someone else checkout', function () {
    $course = sellable();
    $this->actingAs($this->buyer)->post("/courses/{$course->slug}/purchase");
    $reference = Order::sole()->reference;

    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get("/checkout/{$reference}")->assertForbidden();
    $this->actingAs($stranger)->post("/checkout/{$reference}", ['outcome' => 'paid'])->assertForbidden();

    expect(Enrollment::count())->toBe(0);
});

test('a finished checkout cannot be reopened', function () {
    $course = sellable();
    $this->actingAs($this->buyer)->post("/courses/{$course->slug}/purchase");
    $reference = Order::sole()->reference;

    $this->postJson('/payments/callback', callback($reference));

    $this->actingAs($this->buyer)->get("/checkout/{$reference}")->assertStatus(410);
});

test('the gateway page shows the amount actually owed', function () {
    $course = sellable(5000);
    Coupon::factory()->percent(20)->create(['code' => 'FIFTH']);

    $this->actingAs($this->buyer)->post("/courses/{$course->slug}/purchase", ['coupon_code' => 'FIFTH']);

    $this->actingAs($this->buyer)
        ->get('/checkout/'.Order::sole()->reference)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('checkout/gateway')
            ->where('order.total_cents', 4000)
            ->where('order.discount_cents', 1000)
            ->where('order.coupon', 'FIFTH')
        );
});

test('the stand-in gateway does not exist outside local', function () {
    // A fake payment page reachable on a live site would be the worst bug in
    // this project, so prove it by booting the app as production and reading
    // the route table, rather than trusting a condition by eye.
    $routes = Process::env(['APP_ENV' => 'production'])
        ->run('php artisan route:list --except-vendor')
        ->output();

    expect($routes)->not->toContain('checkout/')
        ->and($routes)->not->toContain('payments/callback')
        // Sanity: the command really did list routes.
        ->and($routes)->toContain('courses');
})->skipOnWindows();
