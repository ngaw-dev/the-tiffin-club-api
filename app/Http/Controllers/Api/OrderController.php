<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PlaceOrderRequest;
use App\Http\Requests\Api\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderNoLongerAvailable;
use App\Services\OrderNotFound;
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

    public function show(string $token): JsonResponse
    {
        try {
            $order = $this->orders->findByToken($token);
        } catch (OrderNotFound) {
            return $this->errorResponse('We couldn\'t find that order', 'We couldn\'t find what you\'re looking for', 404);
        } catch (OrderNoLongerAvailable) {
            return $this->errorResponse('This order is no longer available', 'This order is no longer available', 410);
        }

        return $this->successResponse(new OrderResource($order), 'Here\'s your order');
    }

    public function update(UpdateOrderRequest $request, string $token): JsonResponse
    {
        try {
            $order = $this->orders->findByToken($token);
        } catch (OrderNotFound) {
            return $this->errorResponse('We couldn\'t find that order', 'We couldn\'t find what you\'re looking for', 404);
        } catch (OrderNoLongerAvailable) {
            return $this->errorResponse('This order is no longer available', 'This order is no longer available', 410);
        }

        $order = $this->orders->update($order, $request->validated());

        return $this->successResponse(new OrderResource($order->load('items.menuItem')), 'Order updated');
    }

    public function destroy(string $token): JsonResponse
    {
        try {
            $order = $this->orders->findByToken($token);
        } catch (OrderNotFound) {
            return $this->errorResponse('We couldn\'t find that order', 'We couldn\'t find what you\'re looking for', 404);
        } catch (OrderNoLongerAvailable) {
            return $this->errorResponse('This order is no longer available', 'This order is no longer available', 410);
        }

        $order = $this->orders->cancel($order);

        return $this->successResponse(new OrderResource($order->load('items.menuItem')), 'Order cancelled');
    }
}
