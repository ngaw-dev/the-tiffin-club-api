<?php

use App\Models\Menu;
use App\Services\MenuService;
use App\Services\OrderingWindowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * All test times derive from today's date in the store timezone, so the
 * suite is deterministic no matter which day it runs on.
 */
function aklToday(string $time): Carbon
{
    return Carbon::parse(today(OrderingWindowService::TIMEZONE)->toDateString().' '.$time, OrderingWindowService::TIMEZONE);
}

beforeEach(function () {
    Carbon::setTestNow(aklToday('10:00'));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('opens at 09:00 when the menu is published on time', function () {
    $menu = Menu::factory()->published()->create([
        'published_at' => aklToday('08:30'),
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
        'published_at' => aklToday('10:30'),
        'cutoff_time' => '13:00',
    ]);

    $window = app(OrderingWindowService::class)->forMenu($menu);

    expect($window->opensAt->format('H:i'))->toBe('10:30')
        ->and($window->isOpen())->toBeFalse();

    Carbon::setTestNow(aklToday('10:45'));

    expect(app(OrderingWindowService::class)->forMenu($menu->refresh())->isOpen())->toBeTrue();
});

it('falls back to the global default cutoff when none is set for the day', function () {
    config(['tiffin.default_cutoff_time' => '12:30']);

    $menu = Menu::factory()->published()->create([
        'published_at' => aklToday('08:00'),
        'cutoff_time' => null,
    ]);

    $window = app(OrderingWindowService::class)->forMenu($menu);

    expect($window->closesAt->format('H:i'))->toBe('12:30');
});

it('is closed before the window opens', function () {
    Carbon::setTestNow(aklToday('08:00'));

    $menu = Menu::factory()->published()->create([
        'published_at' => aklToday('07:00'),
        'cutoff_time' => '11:00',
    ]);

    expect(app(OrderingWindowService::class)->forMenu($menu)->isOpen())->toBeFalse();
});

it('is closed after the cutoff time', function () {
    Carbon::setTestNow(aklToday('11:01'));

    $menu = Menu::factory()->published()->create([
        'published_at' => aklToday('08:00'),
        'cutoff_time' => '11:00',
    ]);

    $window = app(OrderingWindowService::class)->forMenu($menu);

    expect($window->isOpen())->toBeFalse()
        ->and($window->closingSoon())->toBeFalse();
});

it('flags closing soon inside the last 60 minutes before cutoff', function () {
    Carbon::setTestNow(aklToday('10:30'));

    $menu = Menu::factory()->published()->create([
        'published_at' => aklToday('08:00'),
        'cutoff_time' => '11:00',
    ]);

    $window = app(OrderingWindowService::class)->forMenu($menu);

    expect($window->isOpen())->toBeTrue()
        ->and($window->closingSoon())->toBeTrue();
});

it('does not flag closing soon exactly 60 minutes before cutoff', function () {
    Carbon::setTestNow(aklToday('10:00'));

    $menu = Menu::factory()->published()->create([
        'published_at' => aklToday('08:00'),
        'cutoff_time' => '11:00',
    ]);

    expect(app(OrderingWindowService::class)->forMenu($menu)->closingSoon())->toBeFalse();
});

it('allows ordering right up to the cutoff minute', function () {
    Carbon::setTestNow(aklToday('11:00'));

    $menu = Menu::factory()->published()->create([
        'published_at' => aklToday('08:00'),
        'cutoff_time' => '11:00',
    ]);

    expect(app(OrderingWindowService::class)->forMenu($menu)->isOpen())->toBeTrue();
});

it('serves the new day\'s menu immediately after Auckland midnight', function () {
    // 00:30 on tomorrow's date in Auckland — new day, menu not yet published.
    Carbon::setTestNow(aklToday('10:00')->addDay()->setTime(0, 30));

    Menu::factory()->published()->create([
        'menu_date' => today(OrderingWindowService::TIMEZONE)->subDay()->toDateString(),
        'published_at' => aklToday('08:00'),
        'cutoff_time' => '11:00',
    ]);

    $menu = app(MenuService::class)->todaysPublishedMenu();

    expect($menu)->toBeNull();
});

it('reports closed rather than opening later when a menu publishes after its cutoff', function () {
    Carbon::setTestNow(aklToday('13:30'));

    $menu = Menu::factory()->published()->create([
        'published_at' => aklToday('13:00'),
        'cutoff_time' => '11:00',
    ]);

    $window = app(OrderingWindowService::class)->forMenu($menu);

    expect($window->isOpen())->toBeFalse()
        ->and($window->state())->toBe('closed');
});

it('computes everything in Pacific/Auckland even across a UTC midnight rollover', function () {
    // 23:30 in Auckland is 10:30 UTC the same day during NZDT (UTC+13):
    // a UTC clock has not yet crossed midnight, but Auckland nearly has.
    Carbon::setTestNow(aklToday('10:00')->setTime(23, 30)->setTimezone('UTC'));

    $menu = Menu::factory()->published()->create([
        'menu_date' => today(OrderingWindowService::TIMEZONE)->toDateString(),
        'published_at' => aklToday('08:00'),
        'cutoff_time' => '12:00',
    ]);

    $window = app(OrderingWindowService::class)->forMenu($menu);

    expect($window->isOpen())->toBeFalse()
        ->and($window->closesAt->format('H:i'))->toBe('12:00');
});
