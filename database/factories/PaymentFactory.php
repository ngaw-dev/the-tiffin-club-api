<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'customer_id' => fn (array $attributes) => Order::find($attributes['order_id'])->customer_id,
            'amount' => fake()->numberBetween(500, 5000),
            'method' => 'cash',
            'paid_at' => now(),
        ];
    }
}
