<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Carbon;

class MenuService
{
    /** Timezone every menu date lives in. */
    public const TIMEZONE = 'Pacific/Auckland';

    /**
     * Today's published menu, or null when today's menu is not (yet) published.
     */
    public function todaysPublishedMenu(): ?Menu
    {
        return Menu::query()
            ->whereDate('menu_date', Carbon::today(new \DateTimeZone(self::TIMEZONE))->toDateString())
            ->whereNotNull('published_at')
            ->with(['items' => fn ($q) => $q->orderBy('sort_order')])
            ->first();
    }
}
