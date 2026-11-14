<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Carbon;

class OrderingWindowService
{
    /** Timezone every window computation lives in. */
    public const TIMEZONE = 'Pacific/Auckland';

    /** The store's opening hour each day. */
    public const OPENS_AT = '09:00';

    public function __construct(
        private readonly MenuService $menus,
    ) {}

    /**
     * Ordering window for today's published menu, or null when there is
     * no published menu today.
     */
    public function forToday(): ?OrderingWindow
    {
        $menu = $this->menus->todaysPublishedMenu();

        return $menu === null ? null : $this->forMenu($menu);
    }

    /**
     * Ordering window for a menu: opens at max(09:00, publish time);
     * closes at the menu's per-day cutoff, or the global default.
     */
    public function forMenu(Menu $menu): OrderingWindow
    {
        $tz = new \DateTimeZone(self::TIMEZONE);
        $now = Carbon::now($tz);

        $day = Carbon::parse($menu->menu_date->toDateString(), $tz);

        $baseOpen = $day->copy()->setTimeFromTimeString(self::OPENS_AT);
        $publishedAt = Carbon::parse($menu->published_at)->setTimezone($tz);
        $opensAt = $publishedAt->greaterThan($baseOpen) ? $publishedAt : $baseOpen;

        $cutoff = $menu->cutoff_time ?? config('tiffin.default_cutoff_time');
        $closesAt = $day->copy()->setTimeFromTimeString($cutoff);

        return new OrderingWindow($opensAt, $closesAt, $now);
    }
}
