<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentSource;
use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('orders/index', [
            'orders' => $request->user()->orders()
                ->with(['course:id,slug,title', 'coupon:id,code'])
                ->latest()
                ->get()
                ->map(fn (Order $order) => [
                    ...$order->only('id', 'subtotal_cents', 'discount_cents', 'total_cents', 'currency', 'status'),
                    'paid_at' => $order->paid_at,
                    'course' => $order->course->only('slug', 'title'),
                    'coupon' => $order->coupon?->code,
                ]),
        ]);
    }

    /**
     * The whole checkout. No gateway is called — a card charge belongs between
     * validation and the transaction below, and everything downstream of it
     * (order, coupon redemption, enrolment) already works.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('purchase', $course);

        $code = $request->validate([
            'coupon_code' => ['nullable', 'string', 'max:64'],
        ])['coupon_code'] ?? null;

        $user = $request->user();

        DB::transaction(function () use ($course, $user, $code) {
            $coupon = $code ? $this->redeemableCoupon($code) : null;

            $subtotal = $course->price_cents;
            $discount = $coupon?->discountFor($subtotal) ?? 0;
            $total = $subtotal - $discount;

            Order::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'coupon_id' => $coupon?->id,
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'total_cents' => $total,
                'currency' => $course->currency,
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
            ]);

            $coupon?->increment('redeemed_count');

            $course->enrollments()->create([
                'user_id' => $user->id,
                'source' => $total === 0 ? EnrollmentSource::Free : EnrollmentSource::Purchase,
                'started_at' => now(),
            ]);
        });

        return to_route('learn.show', $course)->with('success', "You're enrolled.");
    }

    /**
     * Locks the coupon row before checking its limit, so two people racing for
     * the last redemption cannot both win it.
     */
    private function redeemableCoupon(string $code): Coupon
    {
        $coupon = Coupon::where('code', $code)->lockForUpdate()->first();

        if (! $coupon) {
            throw ValidationException::withMessages(['coupon_code' => 'That coupon code is not valid.']);
        }

        if ($reason = $coupon->rejectionReason()) {
            throw ValidationException::withMessages(['coupon_code' => $reason]);
        }

        return $coupon;
    }
}
