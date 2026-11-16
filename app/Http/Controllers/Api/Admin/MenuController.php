<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminMenuResource;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\QueryBuilder;

class MenuController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $menus = QueryBuilder::for(Menu::class)
            ->allowedFilters('menu_date')
            ->allowedSorts('menu_date')
            ->defaultSort('-menu_date')
            ->with('items')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return $this->paginatedResponse(AdminMenuResource::collection($menus), 'Menus');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'menu_date' => ['required', 'date', 'unique:menus,menu_date'],
            'cutoff_time' => ['nullable', 'date_format:H:i'],
        ]);

        $menu = Menu::create($validated);

        return $this->successResponse(new AdminMenuResource($menu), 'Menu created', 201);
    }

    public function show(Menu $menu): JsonResponse
    {
        return $this->successResponse(new AdminMenuResource($menu->load('items')), 'Menu');
    }

    public function update(Request $request, Menu $menu): JsonResponse
    {
        $validated = $request->validate([
            'menu_date' => ['sometimes', 'date', Rule::unique('menus', 'menu_date')->ignore($menu)],
            'cutoff_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'publish' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('publish', $validated)) {
            $validated['published_at'] = $validated['publish'] ? $menu->published_at ?? now() : null;
            unset($validated['publish']);
        }

        $menu->update($validated);

        return $this->successResponse(new AdminMenuResource($menu->refresh()), 'Menu updated');
    }

    public function destroy(Menu $menu): JsonResponse
    {
        if ($menu->orders()->exists()) {
            return $this->errorResponse(
                errors: 'This menu has orders attached and cannot be deleted.',
                message: $this->getStatusMessage(409),
                statusCode: 409,
            );
        }

        DB::transaction(function () use ($menu): void {
            $menu->items()->delete();
            $menu->delete();
        });

        return $this->successResponse(message: 'Menu deleted');
    }

    public function storeItem(Request $request, Menu $menu): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:'.implode(',', [MenuItem::TYPE_MAIN, MenuItem::TYPE_EXTRA])],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $item = $menu->items()->create($validated);

        return $this->successResponse(new AdminMenuResource($menu->refresh()->load('items')), 'Item added', 201);
    }

    /**
     * Safe to edit freely: OrderItems snapshot unit_price, so existing
     * orders keep their recorded prices.
     */
    public function updateItem(Request $request, Menu $menu, MenuItem $item): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'in:'.implode(',', [MenuItem::TYPE_MAIN, MenuItem::TYPE_EXTRA])],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $item->update($validated);

        return $this->successResponse(new AdminMenuResource($menu->refresh()->load('items')), 'Item updated');
    }

    public function destroyItem(Menu $menu, MenuItem $item): JsonResponse
    {
        $item->delete();

        return $this->successResponse(new AdminMenuResource($menu->refresh()->load('items')), 'Item deleted');
    }
}
