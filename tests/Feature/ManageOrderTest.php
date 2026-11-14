<?php

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Services\OrderingWindowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function placedOrder(array $menuAttrs = []): array
{
    Carbon::setTestNow(today(OrderingWindowService::TIMEZONE)->setTime(10, 0));

    $menu = Menu::factory()->published()->create($menuAttrs + [
        'published_at' => today(OrderingWindowService::TIMEZONE)->setTime(8, 0),
        'cutoff_time' => '11:00',
    ]);

    $main = MenuItem::factory()->main()->create(['menu_id' => $menu->id, 'price' => 1600]);
    $extra = MenuItem::factory()->extra()->create(['menu_id' => $menu->id, 'price' => 400, 'name' => 'Garlic Naan']);

    $response = postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();

    return [$response->json('data.manage_token'), $main, $extra];
}

afterEach(function () {
    Carbon::setTestNow();
    $this->withoutMiddleware(); // reset any throttling state expectations
});

it('shows an order by manage token with total and lifecycle state', function () {
    [$token, $main] = placedOrder();

    getJson("/api/v1/orders/{$token}")
        ->assertOk()
        ->assertJsonPath('data.manage_token', $token)
        ->assertJsonPath('data.status', 'placed')
        ->assertJsonPath('data.total', 1600);
});

it('returns gone for an unknown token', function () {
    getJson('/api/v1/orders/'.str_repeat('a', 64))
        ->assertNotFound();
});

it('edits an order in place until cutoff and appends to the audit trail', function () {
    [$token, $main, $extra] = placedOrder();

    $response = patchJson("/api/v1/orders/{$token}", [
        'items' => [
            ['id' => $main->id, 'quantity' => 2],
            ['id' => $extra->id, 'quantity' => 1],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.total', 3600)
        ->assertJsonPath('data.items.0.quantity', 2);

    expect(Order::where('manage_token', $token)->first()->audits)->toHaveCount(2); // placed + edited
});

it('cannot edit an order after cutoff', function () {
    [$token, $main] = placedOrder();

    Carbon::setTestNow(today(OrderingWindowService::TIMEZONE)->setTime(11, 1));

    patchJson("/api/v1/orders/{$token}", [
        'items' => [['id' => $main->id, 'quantity' => 5]],
    ])->assertStatus(422)->assertJsonValidationErrors('ordering');
});

it('cancels an order until cutoff', function () {
    [$token] = placedOrder();

    deleteJson("/api/v1/orders/{$token}")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

it('cannot cancel an order after cutoff', function () {
    [$token] = placedOrder();

    Carbon::setTestNow(today(OrderingWindowService::TIMEZONE)->setTime(11, 1));

    deleteJson("/api/v1/orders/{$token}")
        ->assertStatus(422)->assertJsonValidationErrors('ordering');
});

it('returns no longer available for an expired token, not a bare 404', function () {
    [$token] = placedOrder();

    Carbon::setTestNow(today(OrderingWindowService::TIMEZONE)->setTime(23, 59, 59)->addSecond());

    getJson("/api/v1/orders/{$token}")
        ->assertGone()
        ->assertJsonPath('message', 'This order is no longer available');
});
