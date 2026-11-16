<?php

use App\Models\GlobalSetting;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\OrderingWindowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function lookupOrder(array $orderOverrides = []): array
{
    Carbon::setTestNow(today(OrderingWindowService::TIMEZONE)->setTime(10, 0));

    $menu = Menu::factory()->published()->create([
        'published_at' => today(OrderingWindowService::TIMEZONE)->setTime(8, 0),
        'cutoff_time' => '11:00',
    ]);
    $main = MenuItem::factory()->main()->create(['menu_id' => $menu->id, 'price' => 1600]);

    $response = postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'phone' => '0211234567',
        'items' => [['id' => $main->id, 'quantity' => 1]],
        ...$orderOverrides,
    ]);
    $response->assertCreated();

    return [$response->json('data.manage_token'), $main];
}

afterEach(function () {
    Carbon::setTestNow();
});

it('returns an order when the token and email match', function () {
    [$token] = lookupOrder();

    postJson('/api/v1/orders/lookup', ['token' => $token, 'email' => 'amol@example.com'])
        ->assertOk()
        ->assertJsonPath('data.manage_token', $token);
});

it('returns an order when the token and phone match', function () {
    [$token] = lookupOrder();

    postJson('/api/v1/orders/lookup', ['token' => $token, 'phone' => '0211234567'])
        ->assertOk()
        ->assertJsonPath('data.manage_token', $token);
});

it('hides the order when the contact does not match', function () {
    [$token] = lookupOrder();

    postJson('/api/v1/orders/lookup', ['token' => $token, 'email' => 'someone_else@example.com'])
        ->assertStatus(404);

    postJson('/api/v1/orders/lookup', ['token' => $token, 'phone' => '0219999999'])
        ->assertStatus(404);
});

it('answers unknown tokens with the same 404', function () {
    lookupOrder();

    postJson('/api/v1/orders/lookup', ['token' => str_repeat('a', 64), 'email' => 'amol@example.com'])
        ->assertStatus(404);
});

it('requires token and at least one contact identifier', function () {
    postJson('/api/v1/orders/lookup', [])
        ->assertUnprocessable()->assertJsonValidationErrors(['token', 'email', 'phone']);
});

it('exposes store settings publicly', function () {
    getJson('/api/v1/settings')
        ->assertOk()
        ->assertJsonPath('data.order_cutoff_time', config('tiffin.default_cutoff_time'))
        ->assertJsonPath('data.delivery_suburbs.0', 'Te Aro')
        ->assertJsonPath('data.pickup_start_time', null)
        ->assertJsonPath('data.pickup_end_time', null)
        ->assertJsonPath('data.store_phone', '021 123 4567')
        ->assertJsonPath('data.store_phone_alternate', '04 555 1234')
        ->assertJsonPath('data.store_email', 'hello@thetiffinclub.co.nz')
        ->assertJsonPath('data.pickup_address', '123 Cuba Street, Te Aro, Wellington')
        ->assertJsonPath('data.pickup_map_url', null);
});

it('serves settings from the key-value rows', function () {
    GlobalSetting::set('order_cutoff_time', '18:45');
    GlobalSetting::set('delivery_suburbs', ['Karori', 'Newtown']);

    getJson('/api/v1/settings')
        ->assertOk()
        ->assertJsonPath('data.order_cutoff_time', '18:45')
        ->assertJsonPath('data.delivery_suburbs', ['Karori', 'Newtown']);
});
