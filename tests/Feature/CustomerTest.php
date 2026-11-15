<?php

use App\Models\Customer;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\OrderingWindowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function openMenuWithMain(): MenuItem
{
    Carbon::setTestNow(today(OrderingWindowService::TIMEZONE)->setTime(10, 0));

    $menu = Menu::factory()->published()->create([
        'published_at' => today(OrderingWindowService::TIMEZONE)->setTime(8, 0),
        'cutoff_time' => '11:00',
    ]);

    return MenuItem::factory()->main()->create(['menu_id' => $menu->id, 'price' => 1600]);
}

afterEach(function () {
    Carbon::setTestNow();
});

it('creates a customer from a first order and links it', function () {
    $main = openMenuWithMain();

    postJson('/api/v1/orders', [
        'fulfilment' => 'delivery',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'phone' => '0211234567',
        'address' => '12 Cuba Street',
        'suburb' => 'Te Aro',
        'delivery_notes' => 'Knock twice',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();

    $customer = Customer::query()->where('email', 'amol@example.com')->first();
    expect($customer)->not->toBeNull()
        ->and($customer->phone)->toBe('0211234567')
        ->and($customer->address)->toBe('12 Cuba Street')
        ->and($customer->suburb)->toBe('Te Aro')
        ->and($customer->orders()->count())->toBe(1);

    $order = $customer->orders()->first();
    expect($order->delivery_notes)->toBe('Knock twice')
        ->and($order->customer->name)->toBe('Amol W')
        ->and($order->customer->address)->toBe('12 Cuba Street');
});

it('reuses the existing customer on a repeat order', function () {
    $main = openMenuWithMain();

    $existing = Customer::create([
        'name' => 'Amol Old',
        'email' => 'amol@example.com',
        'phone' => '0211234567',
    ]);

    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();

    expect($existing->fresh()->orders()->count())->toBe(1)
        ->and(Customer::where('email', 'amol@example.com')->count())->toBe(1);
});

it('prefers the email match when email and phone match different customers', function () {
    $main = openMenuWithMain();

    $byEmail = Customer::create(['name' => 'Email Person', 'email' => 'amol@example.com']);
    $byPhone = Customer::create(['name' => 'Phone Person', 'phone' => '0211234567']);

    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'phone' => '0211234567',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();

    expect($byEmail->fresh()->orders()->count())->toBe(1)
        ->and($byPhone->fresh()->orders()->count())->toBe(0);
});

it('creates a phone-only customer', function () {
    $main = openMenuWithMain();

    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'phone' => '0211234567',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();

    expect(Customer::where('phone', '0211234567')->whereNull('email')->exists())->toBeTrue();
});

it('normalizes email to lowercase and keeps phone digits', function () {
    $main = openMenuWithMain();

    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'email' => 'AMOL@Example.COM',
        'phone' => '021 123 4567',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();

    expect(Customer::where('email', 'amol@example.com')->where('phone', '0211234567')->exists())->toBeTrue();
});
