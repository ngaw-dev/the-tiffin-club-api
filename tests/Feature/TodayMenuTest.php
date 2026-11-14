<?php

use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\MenuService;

use function Pest\Laravel\getJson;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('customer can fetch today\'s published menu', function () {
    $menu = Menu::factory()
        ->published()
        ->has(MenuItem::factory()->main(), 'items')
        ->has(MenuItem::factory()->count(2)->extra(), 'items')
        ->create([
            'menu_date' => today(new DateTimeZone(MenuService::TIMEZONE)),
        ]);

    $response = getJson('/api/v1/menu/today');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.main.name', $menu->items->firstWhere('type', MenuItem::TYPE_MAIN)->name)
        ->assertJsonCount(2, 'data.extras');

    expect($response->json('data.main.price'))
        ->toBe($menu->items->firstWhere('type', MenuItem::TYPE_MAIN)->price);
});

test('unpublished day returns coming soon, not an error', function () {
    expect(Menu::count())->toBe(0);

    getJson('/api/v1/menu/today')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'coming_soon')
        ->assertJsonPath('data.main', null);
});

test('today\'s menu row exists but is unpublished returns coming soon', function () {
    Menu::factory()
        ->has(MenuItem::factory()->main(), 'items')
        ->create(['published_at' => null]);

    getJson('/api/v1/menu/today')
        ->assertOk()
        ->assertJsonPath('data.status', 'coming_soon');
});

test('menu from yesterday is not returned as today\'s menu', function () {
    Menu::factory()
        ->published()
        ->has(MenuItem::factory()->main(), 'items')
        ->create([
            'menu_date' => today(new DateTimeZone(MenuService::TIMEZONE))->subDay(),
        ]);

    getJson('/api/v1/menu/today')
        ->assertOk()
        ->assertJsonPath('data.status', 'coming_soon');
});
