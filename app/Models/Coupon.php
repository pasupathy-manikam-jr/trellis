<?php

namespace App\Models;

use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected $fillable = ['code', 'percent_off', 'amount_off_cents', 'max_redemptions', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'percent_off' => 'integer',
            'amount_off_cents' => 'integer',
            'max_redemptions' => 'integer',
            'redeemed_count' => 'integer',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * Discount in cents, never more than the subtotal — a coupon can take a
     * price to zero but never below it.
     *
     * Percentages round half-up, which favours the customer by at most a cent.
     */
    public function discountFor(int $subtotalCents): int
    {
        $discount = $this->percent_off !== null
            ? (int) round($subtotalCents * $this->percent_off / 100)
            : (int) $this->amount_off_cents;

        return max(0, min($discount, $subtotalCents));
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->max_redemptions !== null && $this->redeemed_count >= $this->max_redemptions;
    }

    public function isRedeemable(): bool
    {
        return ! $this->hasExpired() && ! $this->isExhausted();
    }

    /** Why this coupon cannot be used, or null if it can. */
    public function rejectionReason(): ?string
    {
        return match (true) {
            $this->hasExpired() => 'That coupon has expired.',
            $this->isExhausted() => 'That coupon has been fully redeemed.',
            default => null,
        };
    }
}
