<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = 4900;

        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'subtotal_cents' => $subtotal,
            'discount_cents' => 0,
            'total_cents' => $subtotal,
            'currency' => 'USD',
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ];
    }
}
