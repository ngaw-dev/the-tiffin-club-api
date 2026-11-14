<?php

use App\Models\OrderUpdate;
use App\Services\OrderingWindowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function placeableMenu(): \App\Models\Menu
{
    $menu = \App\Models\Menu::factory()->published()->create([
        'published_at' => today(OrderingWindowService::TIMEZONE)->setTime(8, 0),
        'cutoff_time' => '11:00',
    ]);
    \App\Models\MenuItem::factory()->main()->create(['menu_id' => $menu->id, 'price' => 1600]);

    return $menu;
}

beforeEach(function () {
    config(['queue.default' => 'sync']);
    Mail::fake();

    Carbon::setTestNow(today(OrderingWindowService::TIMEZONE)->setTime(10, 0));

    $this->menu = placeableMenu();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('emails a confirmation when the order has an email', function () {
    $main = $this->menu->items->first();

    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();

    Mail::assertQueued(\App\Mail\OrderConfirmationMail::class, 1);

    expect(OrderUpdate::query()->where('action', 'confirmation_sent')->count())->toBe(1)
        ->and(OrderUpdate::query()->where('action', 'confirmation_skipped')->count())->toBe(0);
});

it('skips and audits the confirmation when the customer has no email', function () {
    $main = $this->menu->items->first();

    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'phone' => '0211234567',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();

    Mail::assertNothingQueued();

    $skip = OrderUpdate::query()->where('action', 'confirmation_skipped')->first();
    expect($skip)->not->toBeNull()
        ->and($skip->actor)->toBe(OrderUpdate::ACTOR_SYSTEM)
        ->and($skip->changes)->toBe(['channel' => 'email', 'reason' => 'no_email'])
        ->and(OrderUpdate::query()->where('action', 'confirmation_sent')->count())->toBe(0);
});

it('audits confirmation_sent with actor system', function () {
    $main = $this->menu->items->first();

    postJson('/api/v1/orders', [
        'fulfilment' => 'delivery',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'phone' => '0211234567',
        'suburb' => 'Te Aro',
        'items' => [['id' => $main->id, 'quantity' => 2]],
    ])->assertCreated();

    $sent = OrderUpdate::query()->where('action', 'confirmation_sent')->first();
    expect($sent->actor)->toBe(OrderUpdate::ACTOR_SYSTEM)
        ->and($sent->changes)->toBe(['channel' => 'email']);
});
