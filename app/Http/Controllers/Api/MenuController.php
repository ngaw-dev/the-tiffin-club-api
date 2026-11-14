<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Services\MenuService;
use App\Services\OrderingWindowService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MenuService $menus,
        private readonly OrderingWindowService $orderingWindows,
    ) {}

    /**
     * Today's menu: the main, extras with prices, and whether the store
     * is open for browsing or the menu is still coming soon. The ordering
     * window is exposed so clients never compute time themselves.
     */
    public function today(): JsonResponse
    {
        $menu = $this->menus->todaysPublishedMenu();

        if ($menu === null) {
            return $this->successResponse([
                'status' => 'coming_soon',
                'main' => null,
                'extras' => [],
            ], 'Today\'s menu is coming soon');
        }

        $window = $this->orderingWindows->forMenu($menu);
        $main = $menu->items->firstWhere('type', MenuItem::TYPE_MAIN);
        $extras = $menu->items->where('type', MenuItem::TYPE_EXTRA)->values();

        return $this->successResponse([
            'status' => 'open',
            'date' => $menu->menu_date->toDateString(),
            'main' => new MenuItemResource($main),
            'extras' => MenuItemResource::collection($extras),
            'ordering' => [
                'state' => $window->state(),
                'open' => $window->isOpen(),
                'closing_soon' => $window->closingSoon(),
                'opens_at' => $window->opensAt->format('H:i'),
                'closes_at' => $window->closesAt->format('H:i'),
            ],
        ], 'Here\'s today\'s menu');
    }
}
