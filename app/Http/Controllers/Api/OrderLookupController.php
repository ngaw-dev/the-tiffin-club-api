<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OrderLookupRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderNoLongerAvailable;
use App\Services\OrderNotFound;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class OrderLookupController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly OrderService $orders) {}

    /**
     * Knowledge-based lookup: manage token + the email or phone used at
     * checkout. Every failure mode answers identically (404) so nothing
     * is revealed about what an order carries.
     */
    public function __invoke(OrderLookupRequest $request): JsonResponse
    {
        try {
            $order = $this->orders->lookup(
                $request->validated('token'),
                $request->validated('email'),
                $request->validated('phone'),
            );
        } catch (OrderNotFound|OrderNoLongerAvailable) {
            return $this->errorResponse(
                'Order not found',
                'We couldn\'t find an order matching those details',
                404,
            );
        }

        return $this->successResponse(new OrderResource($order), 'Here\'s your order');
    }
}
