<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Mail\OrderReceiptMail;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
     * Starts a checkout. The order is created pending and the learner is sent
     * to the gateway; nothing is granted until the gateway confirms. A free
     * course skips the gateway, because there is nothing to charge.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('purchase', $course);

        $code = $request->validate([
            'coupon_code' => ['nullable', 'string', 'max:64'],
        ])['coupon_code'] ?? null;

        $user = $request->user();

        $order = DB::transaction(function () use ($course, $user, $code) {
            $coupon = $code ? $this->redeemableCoupon($code) : null;

            $subtotal = $course->price_cents;
            $discount = $coupon?->discountFor($subtotal) ?? 0;

            return Order::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'coupon_id' => $coupon?->id,
                'reference' => 'chk_'.Str::lower(Str::random(24)),
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'total_cents' => $subtotal - $discount,
                'currency' => $course->currency,
                'status' => OrderStatus::Pending,
            ]);
        });

        if ($order->total_cents === 0) {
            $order->confirm();
            Mail::to($user)->send(new OrderReceiptMail($order->load('course', 'coupon')));

            return to_route('learn.show', $course)->with('success', "You're enrolled.");
        }

        return redirect()->route('checkout.show', $order);
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
