<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Coupon> */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##??')),
            'percent_off' => 20,
        ];
    }

    public function percent(int $percent): static
    {
        return $this->state(fn () => ['percent_off' => $percent, 'amount_off_cents' => null]);
    }

    public function amount(int $cents): static
    {
        return $this->state(fn () => ['amount_off_cents' => $cents, 'percent_off' => null]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function exhausted(): static
    {
        return $this->state(fn () => ['max_redemptions' => 1, 'redeemed_count' => 1]);
    }
}
