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
                'image' => 'menu-items/butter-chicken.jpg',
            ],
            [
                'main' => 'Paneer Tikka Masala with Naan',
                'description' => 'Char-grilled paneer in a creamy tomato masala with fresh butter naan.',
                'price' => 1500,
                'image' => 'menu-items/paneer-tikka-masala.jpg',
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
                'image' => 'menu-items/chole-bhature.jpg',
            ],
            [
                'main' => 'Chicken Biryani with Raita',
                'description' => 'Dum-style chicken biryani layered with saffron rice and served with raita.',
                'price' => 1700,
                'image' => 'menu-items/chicken-biryani.jpg',
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
                'image_path' => $day['image'] ?? null,
                'price' => $day['price'],
                'sort_order' => 1,
            ]);

            // Demo extras: most days have plain items, some carry images.
            $extras = [
                ['name' => 'Garlic Naan', 'price' => 400, 'sort_order' => 2, 'image' => 'menu-items/garlic-naan.jpg'],
                ['name' => 'Papadum', 'price' => 200, 'sort_order' => 3],
                ['name' => 'Gulab Jamun', 'price' => 500, 'sort_order' => 4, 'image' => 'menu-items/gulab-jamun.jpg'],
            ];
            foreach ($extras as $extra) {
                MenuItem::create([
                    'menu_id' => $menu->id,
                    'type' => MenuItem::TYPE_EXTRA,
                    'name' => $extra['name'],
                    'image_path' => $extra['image'] ?? null,
                    'price' => $extra['price'],
                    'sort_order' => $extra['sort_order'],
                ]);
            }
        }
    }
}
