<?php

use App\Models\Menu;
use App\Services\MenuService;
use App\Services\OrderingWindowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-14 10:00', OrderingWindowService::TIMEZONE));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('opens at 09:00 when the menu is published on time', function () {
    $menu = Menu::factory()->published()->create([
        'published_at' => Carbon::parse('2026-11-14 08:30', OrderingWindowService::TIMEZONE),
        'cutoff_time' => '11:00',
    ]);

    $window = app(OrderingWindowService::class)->forMenu($menu);

    expect($window->isOpen())->toBeTrue()
        ->and($window->opensAt->format('H:i'))->toBe('09:00')
        ->and($window->closesAt->format('H:i'))->toBe('11:00')
        ->and($window->closingSoon())->toBeFalse();
});

it('opens at publish time when the menu is published late', function () {
    $menu = Menu::factory()->published()->create([
        'published_at' => Carbon::parse('2026-11-14 10:30', OrderingWindowService::TIMEZONE),
        'cutoff_time' => '13:00',
    ]);

    $window = app(OrderingWindowService::class)->forMenu($menu);

    expect($window->opensAt->format('H:i'))->toBe('10:30')
        ->and($window->isOpen())->toBeFalse();

    Carbon::setTestNow(Carbon::parse('2026-11-14 10:45', OrderingWindowService::TIMEZONE));

    expect(app(OrderingWindowService::class)->forMenu($menu->refresh())->isOpen())->toBeTrue();
});

it('falls back to the global default cutoff when none is set for the day', function () {
    config(['tiffin.default_cutoff_time' => '12:30']);

    $menu = Menu::factory()->published()->create([
        'published_at' => Carbon::parse('2026-11-14 08:00', OrderingWindowService::TIMEZONE),
        'cutoff_time' => null,
    ]);

    $window = app(OrderingWindowService::class)->forMenu($menu);

    expect($window->closesAt->format('H:i'))->toBe('12:30');
});

it('is closed before the window opens', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-14 08:00', OrderingWindowService::TIMEZONE));

    $menu = Menu::factory()->published()->create([
        'published_at' => Carbon::parse('2026-11-14 07:00', OrderingWindowService::TIMEZONE),
        'cutoff_time' => '11:00',
    ]);

    expect(app(OrderingWindowService::class)->forMenu($menu)->isOpen())->toBeFalse();
});

it('is closed after the cutoff time', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-14 11:01', OrderingWindowService::TIMEZONE));

    $menu = Menu::factory()->published()->create([
        'published_at' => Carbon::parse('2026-11-14 08:00', OrderingWindowService::TIMEZONE),
        'cutoff_time' => '11:00',
    ]);

    $window = app(OrderingWindowService::class)->forMenu($menu);

    expect($window->isOpen())->toBeFalse()
        ->and($window->closingSoon())->toBeFalse();
});

it('flags closing soon inside the last 60 minutes before cutoff', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-14 10:30', OrderingWindowService::TIMEZONE));

    $menu = Menu::factory()->published()->create([
        'published_at' => Carbon::parse('2026-11-14 08:00', OrderingWindowService::TIMEZONE),
        'cutoff_time' => '11:00',
    ]);

    $window = app(OrderingWindowService::class)->forMenu($menu);

    expect($window->isOpen())->toBeTrue()
        ->and($window->closingSoon())->toBeTrue();
});

it('does not flag closing soon exactly 60 minutes before cutoff', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-14 10:00', OrderingWindowService::TIMEZONE));

    $menu = Menu::factory()->published()->create([
        'published_at' => Carbon::parse('2026-11-14 08:00', OrderingWindowService::TIMEZONE),
        'cutoff_time' => '11:00',
    ]);

    expect(app(OrderingWindowService::class)->forMenu($menu)->closingSoon())->toBeFalse();
});

it('allows ordering right up to the cutoff minute', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-14 11:00', OrderingWindowService::TIMEZONE));

    $menu = Menu::factory()->published()->create([
        'published_at' => Carbon::parse('2026-11-14 08:00', OrderingWindowService::TIMEZONE),
        'cutoff_time' => '11:00',
    ]);

    expect(app(OrderingWindowService::class)->forMenu($menu)->isOpen())->toBeTrue();
});

it('serves the new day\'s menu immediately after Auckland midnight', function () {
    // 00:30 on Nov 15 in Auckland — new day, menu not yet published.
    Carbon::setTestNow(Carbon::parse('2026-11-15 00:30', OrderingWindowService::TIMEZONE));

    Menu::factory()->published()->create([
        'menu_date' => '2026-11-14',
        'published_at' => Carbon::parse('2026-11-14 08:00', OrderingWindowService::TIMEZONE),
        'cutoff_time' => '11:00',
    ]);

    $menu = app(MenuService::class)->todaysPublishedMenu();

    expect($menu)->toBeNull();
});

it('reports closed rather than opening later when a menu publishes after its cutoff', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-14 13:30', OrderingWindowService::TIMEZONE));

    $menu = Menu::factory()->published()->create([
        'published_at' => Carbon::parse('2026-11-14 13:00', OrderingWindowService::TIMEZONE),
        'cutoff_time' => '11:00',
    ]);

    $window = app(OrderingWindowService::class)->forMenu($menu);

    expect($window->isOpen())->toBeFalse()
        ->and($window->state())->toBe('closed');
});

it('computes everything in Pacific/Auckland even across a UTC midnight rollover', function () {
    // 2026-11-14 23:30 UTC is still 2026-11-15 12:30 in Auckland (NZDT, UTC+13).
    Carbon::setTestNow(Carbon::parse('2026-11-14 23:30', 'UTC'));

    $menu = Menu::factory()->published()->create([
        'menu_date' => '2026-11-15',
        'published_at' => Carbon::parse('2026-11-15 09:00', OrderingWindowService::TIMEZONE),
        'cutoff_time' => '12:00',
    ]);

    $window = app(OrderingWindowService::class)->forMenu($menu);

    expect($window->isOpen())->toBeFalse()
        ->and($window->closesAt->format('H:i'))->toBe('12:00');
});
