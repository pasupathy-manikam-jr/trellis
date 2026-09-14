<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/orders/index', [
            'orders' => Order::with(['user:id,name,email', 'course:id,slug,title', 'coupon:id,code'])
                ->latest()
                ->get()
                ->map(fn (Order $order) => [
                    ...$order->only('id', 'subtotal_cents', 'discount_cents', 'total_cents', 'currency', 'status'),
                    'paid_at' => $order->paid_at,
                    'user' => $order->user->only('name', 'email'),
                    'course' => $order->course->only('slug', 'title'),
                    'coupon' => $order->coupon?->code,
                ]),
        ]);
    }

    /** A refund revokes access — otherwise the money goes back and the course does not. */
    public function refund(Order $order): RedirectResponse
    {
        abort_if($order->status === OrderStatus::Refunded, 409, 'Already refunded.');

        DB::transaction(function () use ($order) {
            $order->update([
                'status' => OrderStatus::Refunded,
                'refunded_at' => now(),
            ]);

            $order->course->enrollments()->where('user_id', $order->user_id)->delete();

            $order->coupon?->decrement('redeemed_count');
        });

        return back()->with('success', 'Refunded and access revoked.');
    }
}
