<?php

use App\Http\Middleware\SimulatedTime;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\OrderingWindowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

test('X-Simulated-Time header shifts the effective now', function () {
    Menu::factory()
        ->published()
        ->has(MenuItem::factory()->main(), 'items')
        ->create([
            'menu_date' => Carbon::parse('2026-11-10', new DateTimeZone(OrderingWindowService::TIMEZONE)),
        ]);

    $response = getJson('/api/v1/menu/today', [
        SimulatedTime::HEADER => '2026-11-10T10:00:00+13:00',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'open');
    expect($response->headers->get('X-App-Time-Now'))->toBe('2026-11-10T10:00:00+13:00');
});

test('X-Simulated-Time header is ignored in production', function () {
    app()->detectEnvironment(fn () => 'production');

    Menu::factory()
        ->published()
        ->has(MenuItem::factory()->main(), 'items')
        ->create(['menu_date' => '2026-11-10']);

    $response = getJson('/api/v1/menu/today', [
        SimulatedTime::HEADER => '2026-11-10T10:00:00+13:00',
    ]);

    app()->detectEnvironment(fn () => 'testing');

    $response->assertOk();
    expect($response->headers->get('X-App-Time-Now'))->toBeNull();
});

test('unparseable X-Simulated-Time header returns 400', function () {
    getJson('/api/v1/menu/today', [
        SimulatedTime::HEADER => 'not-a-date',
    ])->assertStatus(400);
});
