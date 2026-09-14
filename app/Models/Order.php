<?php

namespace App\Models;

use App\Enums\EnrollmentSource;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'course_id', 'coupon_id', 'reference', 'subtotal_cents',
        'discount_cents', 'total_cents', 'currency', 'status',
        'paid_at', 'refunded_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    /**
     * Turns a pending order into access. This is the only place enrolment is
     * granted by a purchase, and it is safe to call more than once — a gateway
     * may deliver the same confirmation twice, or out of order with the
     * browser redirect.
     *
     * Returns true only for the call that actually did the work.
     */
    public function confirm(): bool
    {
        return DB::transaction(function () {
            $fresh = static::whereKey($this->getKey())->lockForUpdate()->first();

            if (! $fresh?->isPending()) {
                return false;
            }

            $fresh->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

            // ponytail: the limit is checked when checkout starts and spent
            // here, so two people racing for the last one can both finish.
            // Reserve-and-release if that ever matters.
            $fresh->coupon()->first()?->increment('redeemed_count');

            $fresh->course->enrollments()->firstOrCreate(
                ['user_id' => $fresh->user_id],
                [
                    'source' => $fresh->total_cents === 0
                        ? EnrollmentSource::Free
                        : EnrollmentSource::Purchase,
                    'started_at' => now(),
                ],
            );

            $this->setRawAttributes($fresh->getAttributes(), true);

            return true;
        });
    }

    public function fail(string $reason): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        $this->update(['status' => OrderStatus::Failed, 'failure_reason' => $reason]);

        return true;
    }
}
