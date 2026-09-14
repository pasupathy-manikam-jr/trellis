<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/coupons/index', [
            'coupons' => Coupon::withCount('orders')->latest()->get()
                ->map(fn (Coupon $c) => [
                    ...$c->only('id', 'code', 'percent_off', 'amount_off_cents', 'max_redemptions', 'redeemed_count'),
                    'expires_at' => $c->expires_at,
                    'redeemable' => $c->isRedeemable(),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('coupons', 'code')],
            'kind' => ['required', Rule::in(['percent', 'amount'])],
            'percent_off' => ['required_if:kind,percent', 'nullable', 'integer', 'min:1', 'max:100'],
            'amount_off_cents' => ['required_if:kind,amount', 'nullable', 'integer', 'min:1'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        Coupon::create([
            'code' => strtoupper($data['code']),
            // Exactly one of the two is stored, so discountFor() never has to guess.
            'percent_off' => $data['kind'] === 'percent' ? $data['percent_off'] : null,
            'amount_off_cents' => $data['kind'] === 'amount' ? $data['amount_off_cents'] : null,
            'max_redemptions' => $data['max_redemptions'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return back()->with('success', 'Coupon created.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return back()->with('success', 'Coupon deleted.');
    }
}
