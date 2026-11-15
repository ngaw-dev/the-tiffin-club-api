<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PlaceOrderRequest;
use App\Http\Requests\Api\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\ClerkTokenService;
use App\Services\OrderNoLongerAvailable;
use App\Services\OrderNotFound;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly OrderService $orders,
        private readonly ClerkTokenService $clerkTokens,
    ) {}

    /**
     * Place an order for today's meal. Public for guests; when a
     * valid Clerk Bearer JWT is presented the payload email must
     * match it, and the order links to the email-matched customer.
     * Returns the manage token — the sole handle on the order.
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $this->clerkTokens->assertPayloadEmailMatches($request, $request->validated('email'));

        return $this->successResponse(
            new OrderResource($this->orders->place($request)->load('items.menuItem', 'customer')),
            'Order placed — pay in person',
            201,
        );
    }

    public function show(Request $request, string $token): JsonResponse
    {
        return $this->withOrder($token, fn () => $this->successResponse(
            new OrderResource($this->orders->findByToken($token)),
            'Here\'s your order',
        ));
    }

    public function update(UpdateOrderRequest $request, string $token): JsonResponse
    {
        return $this->withOrder($token, fn () => $this->successResponse(
            new OrderResource($this->orders->update($this->orders->findByToken($token), $request->validated())->load('items.menuItem', 'customer')),
            'Order updated',
        ));
    }

    public function destroy(Request $request, string $token): JsonResponse
    {
        return $this->withOrder($token, fn () => $this->successResponse(
            new OrderResource($this->orders->cancel($this->orders->findByToken($token))->load('items.menuItem', 'customer')),
            'Order cancelled',
        ));
    }

    /**
     * Run the action with unified not-found (404) and no-longer-available
     * (410) handling for token-addressed order endpoints.
     */
    private function withOrder(string $token, callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (OrderNotFound) {
            return $this->errorResponse('We couldn\'t find that order', 'We couldn\'t find what you\'re looking for', 404);
        } catch (OrderNoLongerAvailable) {
            return $this->errorResponse('This order is no longer available', 'This order is no longer available', 410);
        }
    }
}
