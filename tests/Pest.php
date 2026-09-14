<?php

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Drives a purchase all the way through the stand-in gateway.
 *
 * Buying is two steps now — start a checkout, then the gateway confirms out of
 * band — so tests that only care about the end state go through here rather
 * than repeating both halves.
 */
function buyCourse(
    TestCase $test,
    Course $course,
    User $buyer,
    array $payload = [],
): Order {
    $test->actingAs($buyer)->post("/courses/{$course->slug}/purchase", $payload);

    $order = Order::where('user_id', $buyer->id)
        ->where('course_id', $course->id)
        ->latest('id')
        ->firstOrFail();

    if ($order->isPending()) {
        $test->postJson('/payments/callback', [
            'reference' => $order->reference,
            'outcome' => 'paid',
            'secret' => config('services.fake_gateway.secret'),
        ]);
    }

    return $order->fresh();
}
