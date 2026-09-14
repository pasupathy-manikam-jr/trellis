<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Mail\OrderReceiptMail;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * The gateway's word on whether money moved. This is the only thing that turns
 * a pending order into access — the browser redirect after checkout is just a
 * page, and may never happen.
 *
 * Safe to call repeatedly with the same reference: a gateway will retry until
 * it gets a 2xx, and may deliver out of order.
 */
class PaymentCallbackController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string'],
            'outcome' => ['required', 'in:paid,declined'],
            'secret' => ['required', 'string'],
        ]);

        // Stands in for signature verification. A callback nobody can forge is
        // the whole reason this endpoint can be unauthenticated.
        abort_unless(
            hash_equals((string) config('services.fake_gateway.secret'), $data['secret']),
            403,
        );

        $order = Order::where('reference', $data['reference'])->first();

        // Unknown reference: acknowledge it so the gateway stops retrying
        // something we will never understand.
        if (! $order) {
            return response()->json(['status' => 'ignored']);
        }

        if ($data['outcome'] === 'declined') {
            $order->fail('Card declined');

            return response()->json(['status' => 'failed']);
        }

        // confirm() returns false when this callback is a duplicate, which is
        // what stops a second enrolment and a second receipt.
        if ($order->confirm()) {
            Mail::to($order->user)->send(new OrderReceiptMail($order->load('course', 'coupon')));

            return response()->json(['status' => 'confirmed']);
        }

        return response()->json(['status' => 'already_handled']);
    }
}
