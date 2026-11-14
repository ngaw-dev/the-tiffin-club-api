<?php

use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\OrderingWindowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function openMenuWithItems(): array
{
    Carbon::setTestNow(today(OrderingWindowService::TIMEZONE)->setTime(10, 0));

    $menu = Menu::factory()->published()->create([
        'published_at' => today(OrderingWindowService::TIMEZONE)->setTime(8, 0),
        'cutoff_time' => '11:00',
    ]);

    return [
        $menu,
        MenuItem::factory()->main()->create(['menu_id' => $menu->id, 'price' => 1600]),
        MenuItem::factory()->extra()->create(['menu_id' => $menu->id, 'price' => 400, 'name' => 'Garlic Naan']),
    ];
}

afterEach(function () {
    Carbon::setTestNow();
});

it('places a guest order and returns a 64-hex manage token with the server-computed total', function () {
    [, $main, $extra] = openMenuWithItems();

    $response = postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'items' => [
            ['id' => $main->id, 'quantity' => 2],
            ['id' => $extra->id, 'quantity' => 1],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $token = $response->json('data.manage_token');
    expect($token)->toMatch('/^[a-f0-9]{64}$/')
        ->and($response->json('data.total'))->toBe(3600) // 2 x 1600 + 1 x 400
        ->and($response->json('data.payment'))->toBe('pay_in_person');
});

it('rejects an order containing an item that is not on today\'s menu', function () {
    [, $main] = openMenuWithItems();
    $foreign = MenuItem::factory()->extra()->create([
        'menu_id' => Menu::factory()->create([
            'menu_date' => today(OrderingWindowService::TIMEZONE)->subDay(),
        ])->id,
    ]);

    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'items' => [
            ['id' => $main->id, 'quantity' => 1],
            ['id' => $foreign->id, 'quantity' => 1],
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors('items.1.id');
});

it('rejects an order when the ordering window is closed', function () {
    [, $main] = openMenuWithItems();

    Carbon::setTestNow(today(OrderingWindowService::TIMEZONE)->setTime(11, 1));

    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertStatus(422)->assertJsonValidationErrors('ordering');
});

it('requires a valid suburb from global settings for delivery orders; phone optional', function () {
    [, $main] = openMenuWithItems();

    $base = [
        'fulfilment' => 'delivery',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'address' => '12 Cuba Street',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ];

    postJson('/api/v1/orders', $base)
        ->assertUnprocessable()->assertJsonValidationErrors('suburb');

    postJson('/api/v1/orders', $base + ['suburb' => 'Rotorua'])
        ->assertUnprocessable()->assertJsonValidationErrors('suburb');

    postJson('/api/v1/orders', $base + ['suburb' => 'Te Aro'])
        ->assertCreated();
});

it('requires at least one of email or phone', function () {
    [, $main] = openMenuWithItems();

    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone']);

    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'phone' => '0211234567',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();
});

it('does not require phone or suburb for pickup orders', function () {
    [, $main] = openMenuWithItems();

    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'phone' => '0211234567',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();
});

it('rejects an order when no menu is published for today yet', function () {
    // No menu created at all.
    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'items' => [['id' => 1, 'quantity' => 1]],
    ])->assertStatus(422)->assertJsonValidationErrors(['items.0.id', 'ordering']);
});

it('does not require an address for delivery orders', function () {
    [, $main] = openMenuWithItems();

    postJson('/api/v1/orders', [
        'fulfilment' => 'delivery',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'phone' => '0211234567',
        'suburb' => 'Te Aro',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();
});
