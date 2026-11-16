<?php

use App\Mail\OrderConfirmationMail;
use App\Mail\OrderSummaryMail;
use App\Models\GlobalSetting;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\OrderUpdate;
use App\Services\OrderingWindowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function placeableMenu(): Menu
{
    $menu = Menu::factory()->published()->create([
        'published_at' => today(OrderingWindowService::TIMEZONE)->setTime(8, 0),
        'cutoff_time' => '11:00',
    ]);
    MenuItem::factory()->main()->create(['menu_id' => $menu->id, 'price' => 1600]);

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

it('emails the customer and the store owner when an order is placed', function () {
    GlobalSetting::set('store_email', 'owner@tiffin.test');
    $main = $this->menu->items->first();

    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'email' => 'amol@example.com',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();

    Mail::assertQueued(OrderConfirmationMail::class, 1);
    Mail::assertQueued(OrderSummaryMail::class, 1); // owner

    expect(OrderUpdate::query()->where('action', 'confirmation_sent')->count())->toBe(1)
        ->and(OrderUpdate::query()->where('action', 'confirmation_skipped')->count())->toBe(0)
        ->and(OrderUpdate::query()->where('action', 'owner_notified')->count())->toBe(1);
});

it('skips and audits the confirmation when the customer has no email', function () {
    GlobalSetting::set('store_email', null);
    $main = $this->menu->items->first();

    postJson('/api/v1/orders', [
        'fulfilment' => 'pickup',
        'name' => 'Amol W',
        'phone' => '0211234567',
        'items' => [['id' => $main->id, 'quantity' => 1]],
    ])->assertCreated();

    Mail::assertNotQueued(OrderSummaryMail::class); // no customer email; owner skipped too (no store email set)

    $skip = OrderUpdate::query()->where('action', 'confirmation_skipped')->first();
    expect($skip)->not->toBeNull()
        ->and($skip->actor)->toBe(OrderUpdate::ACTOR_SYSTEM)
        ->and($skip->changes)->toBe(['channel' => 'email', 'reason' => 'no_email'])
        ->and(OrderUpdate::query()->where('action', 'confirmation_sent')->count())->toBe(0)
        ->and(OrderUpdate::query()->where('action', 'owner_notified_skipped')->count())->toBe(1);
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
