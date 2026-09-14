<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Stands in for a hosted checkout while there is no payment provider.
 *
 * It exists to exercise the shape a real gateway forces on us — leave the
 * site, come back later, and be told the outcome by a separate callback that
 * may arrive twice, early, or not at all. The card form is the easy part; the
 * asynchrony is where the bugs live.
 *
 * Registered only in the local environment. See routes/web.php.
 */
class FakeGatewayController extends Controller
{
    public function show(Request $request, Order $order): Response
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless($order->isPending(), 410, 'This checkout is already finished.');

        return Inertia::render('checkout/gateway', [
            'order' => [
                'reference' => $order->reference,
                'total_cents' => $order->total_cents,
                'subtotal_cents' => $order->subtotal_cents,
                'discount_cents' => $order->discount_cents,
                'currency' => $order->currency,
                'course' => $order->course->only('slug', 'title'),
                'coupon' => $order->coupon?->code,
            ],
        ]);
    }

    /** The learner picks what the "bank" does. */
    public function simulate(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless($order->isPending(), 410);

        $outcome = $request->validate([
            'outcome' => ['required', Rule::in(['paid', 'declined', 'abandoned'])],
        ])['outcome'];

        if ($outcome === 'abandoned') {
            // Exactly what a real abandonment looks like: nothing is sent, and
            // the order simply stays pending.
            return to_route('courses.show', $order->course)
                ->with('success', 'Checkout cancelled — nothing was charged.');
        }

        // The gateway calls us back out of band; the browser redirect below is
        // a separate journey that must not be the thing granting access.
        app(PaymentCallbackController::class)->handle(
            $request->merge([
                'reference' => $order->reference,
                'outcome' => $outcome,
                'secret' => config('services.fake_gateway.secret'),
            ])
        );

        return $outcome === 'paid'
            ? to_route('learn.show', $order->course)->with('success', "Payment accepted. You're enrolled.")
            : to_route('courses.show', $order->course)->with('success', 'Your card was declined. Nothing was charged.');
    }
}
