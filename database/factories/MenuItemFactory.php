<?php

namespace Database\Factories;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MenuItem> */
class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => MenuItem::TYPE_EXTRA,
            'name' => $this->faker->words(2, true),
            'description' => null,
            'price' => $this->faker->numberBetween(300, 1500),
            'sort_order' => 0,
        ];
    }

    public function main(): static
    {
        return $this->state(fn () => [
            'type' => MenuItem::TYPE_MAIN,
            'description' => $this->faker->sentence(),
        ]);
    }

    public function extra(): static
    {
        return $this->state(fn () => ['type' => MenuItem::TYPE_EXTRA]);
    }
}
