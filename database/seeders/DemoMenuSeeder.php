<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\MenuService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoMenuSeeder extends Seeder
{
    /**
     * Demo menus for the next 7 days, each with a rotating main and extras, published.
     */
    public function run(): void
    {
        $today = Carbon::today(new \DateTimeZone(MenuService::TIMEZONE));

        $week = [
            [
                'main' => 'Butter Chicken with Jeera Rice',
                'description' => 'Slow-cooked butter chicken served with cumin rice and a side of raita.',
                'price' => 1600,
            ],
            [
                'main' => 'Paneer Tikka Masala with Naan',
                'description' => 'Char-grilled paneer in a creamy tomato masala with fresh butter naan.',
                'price' => 1500,
            ],
            [
                'main' => 'Dal Makhani with Basmati Rice',
                'description' => 'Black lentils simmered overnight, finished with cream, served with basmati rice.',
                'price' => 1300,
            ],
            [
                'main' => 'Chole Bhature',
                'description' => 'Spiced chickpea curry with fluffy fried bhature and pickled onion.',
                'price' => 1400,
            ],
            [
                'main' => 'Chicken Biryani with Raita',
                'description' => 'Dum-style chicken biryani layered with saffron rice and served with raita.',
                'price' => 1700,
            ],
            [
                'main' => 'Palak Paneer with Roti',
                'description' => 'Spinach gravy with soft paneer cubes, served with whole wheat roti.',
                'price' => 1400,
            ],
            [
                'main' => 'Rajma Chawal',
                'description' => 'Kidney bean curry in a rich tomato gravy served over steamed rice.',
                'price' => 1300,
            ],
        ];

        foreach ($week as $index => $day) {
            $menu = Menu::create([
                'menu_date' => $today->copy()->addDays($index),
                'published_at' => now(),
            ]);

            MenuItem::create([
                'menu_id' => $menu->id,
                'type' => MenuItem::TYPE_MAIN,
                'name' => $day['main'],
                'description' => $day['description'],
                'price' => $day['price'],
                'sort_order' => 1,
            ]);

            MenuItem::create(['menu_id' => $menu->id, 'type' => 'extra', 'name' => 'Garlic Naan', 'price' => 400, 'sort_order' => 2]);
            MenuItem::create(['menu_id' => $menu->id, 'type' => 'extra', 'name' => 'Papadum', 'price' => 200, 'sort_order' => 3]);
            MenuItem::create(['menu_id' => $menu->id, 'type' => 'extra', 'name' => 'Gulab Jamun', 'price' => 500, 'sort_order' => 4]);
        }
    }
}
