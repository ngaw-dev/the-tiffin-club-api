<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<\App\Models\Menu> */
class MenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'menu_date' => Carbon::today(new \DateTimeZone('Pacific/Auckland')),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['published_at' => $this->faker->dateTimeThisYear()]);
    }
}
