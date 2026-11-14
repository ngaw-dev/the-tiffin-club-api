<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TodayMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_fetch_todays_published_menu(): void
    {
        $menu = Menu::factory()
            ->published()
            ->has(MenuItem::factory()->main(), 'items')
            ->has(MenuItem::factory()->count(2)->extra(), 'items')
            ->create([
                'menu_date' => Carbon::today(new \DateTimeZone('Pacific/Auckland')),
            ]);

        $response = $this->getJson('/api/v1/menu/today');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.main.name', $menu->items->firstWhere('type', 'main')->name)
            ->assertJsonCount(2, 'data.extras');

        $this->assertEquals(
            $menu->items->firstWhere('type', 'main')->price,
            $response->json('data.main.price'),
        );
    }

    public function test_unpublished_day_returns_coming_soon_not_error(): void
    {
        $this->assertDatabaseCount('menus', 0);

        $this->getJson('/api/v1/menu/today')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'coming_soon')
            ->assertJsonPath('data.main', null);
    }

    public function test_menu_from_yesterday_is_not_returned_as_todays_menu(): void
    {
        Menu::factory()
            ->published()
            ->has(MenuItem::factory()->main(), 'items')
            ->create([
                'menu_date' => Carbon::yesterday(new \DateTimeZone('Pacific/Auckland')),
            ]);

        $this->getJson('/api/v1/menu/today')
            ->assertOk()
            ->assertJsonPath('data.status', 'coming_soon');
    }
}
