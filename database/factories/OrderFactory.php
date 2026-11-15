<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $placedAt = now();

        return [
            // shortcut: no CustomerFactory exists; always create with ->for(Customer) or customer_id set
            'customer_id' => fn () => Customer::create(['name' => fake()->name()])->id,
            'menu_id' => Menu::factory(),
            'status' => Order::STATUS_CONFIRMED,
            'fulfilment' => fake()->randomElement([Order::FULFILMENT_PICKUP, Order::FULFILMENT_DELIVERY]),
            'delivery_notes' => null,
            'manage_token' => bin2hex(random_bytes(32)),
            'placed_at' => $placedAt,
            'expires_at' => $placedAt->copy()->endOfDay(),
        ];
    }
}
