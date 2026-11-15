<?php

// app/Http/Controllers/Api/MyOrdersController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Clerk-authenticated order history. Every order on the
 * email-matched customer row — guest history included. Returns an
 * empty list when no customer exists yet; never creates one.
 */
class MyOrdersController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        if ($customer === null) {
            return $this->successResponse(
                OrderResource::collection([]),
                'Here are your orders',
            );
        }

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->with('items.menuItem', 'customer')
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get();

        return $this->successResponse(
            OrderResource::collection($orders),
            'Here are your orders',
        );
    }
}
