<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly OrderService $orders) {}

    /**
     * Place a guest order for today's meal. Returns the manage token —
     * the sole handle on the order.
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $order = $this->orders->place($request);

        return $this->successResponse(
            new OrderResource($order->load('items.menuItem')),
            'Order placed — pay in person',
            201,
        );
    }
}
