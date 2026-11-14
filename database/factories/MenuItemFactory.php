<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\MenuItem> */
class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'extra',
            'name' => $this->faker->words(2, true),
            'description' => null,
            'price' => $this->faker->numberBetween(300, 1500),
            'sort_order' => 0,
        ];
    }

    public function main(): static
    {
        return $this->state(fn () => [
            'type' => 'main',
            'description' => $this->faker->sentence(),
        ]);
    }

    public function extra(): static
    {
        return $this->state(fn () => ['type' => 'extra']);
    }
}
