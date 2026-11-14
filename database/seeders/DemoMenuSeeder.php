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
     * Demo menu for today: butter chicken main with two extras, published.
     */
    public function run(): void
    {
        $menu = Menu::create([
            'menu_date' => Carbon::today(new \DateTimeZone(MenuService::TIMEZONE)),
            'published_at' => now(),
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'type' => 'main',
            'name' => 'Butter Chicken with Jeera Rice',
            'description' => 'Slow-cooked butter chicken served with cumin rice and a side of raita.',
            'price' => 1600,
            'sort_order' => 1,
        ]);

        MenuItem::create(['menu_id' => $menu->id, 'type' => 'extra', 'name' => 'Garlic Naan', 'price' => 400, 'sort_order' => 2]);
        MenuItem::create(['menu_id' => $menu->id, 'type' => 'extra', 'name' => 'Papadum', 'price' => 200, 'sort_order' => 3]);
        MenuItem::create(['menu_id' => $menu->id, 'type' => 'extra', 'name' => 'Gulab Jamun', 'price' => 500, 'sort_order' => 4]);
    }
}
