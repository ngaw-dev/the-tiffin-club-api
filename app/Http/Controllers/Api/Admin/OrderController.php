<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminOrderResource;
use App\Models\Order;
use App\Models\OrderUpdate;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $orders = QueryBuilder::for(Order::class)
            ->allowedFilters(
                'status',
                'fulfilment',
                AllowedFilter::exact('customer_id'),
                AllowedFilter::exact('menu_id'),
            )
            ->allowedSorts('placed_at', 'total')
            ->defaultSort('-placed_at')
            ->with(['customer', 'payments'])
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return $this->paginatedResponse(AdminOrderResource::collection($orders), 'Orders');
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['customer', 'items.menuItem', 'payments']);

        return $this->successResponse(new AdminOrderResource($order), 'Order');
    }

    /**
     * Status-only updates, mirroring the staff panel: the order's
     * contents are immutable after placement.
     */
    public function update(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', [
                Order::STATUS_CONFIRMED,
                Order::STATUS_READY_FOR_PICKUP,
                Order::STATUS_OUT_FOR_DELIVERY,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
            ])],
        ]);

        if ($validated['status'] !== $order->status && ! $order->canTransitionTo($validated['status'])) {
            return $this->errorResponse(
                errors: "An order can't move from {$order->status} to {$validated['status']}.",
                message: 'Order not updated',
                statusCode: 422,
            );
        }

        if ($validated['status'] === Order::STATUS_CANCELLED && $order->payments()->sum('amount') > 0) {
            return $this->errorResponse(
                errors: 'This order has recorded payments and can\'t be cancelled. Contact the store to resolve payment.',
                message: 'Order not cancelled',
                statusCode: 422,
            );
        }

        DB::transaction(function () use ($order, $validated): void {
            if ($order->status === $validated['status']) {
                return;
            }

            $changes = ['status' => ['from' => $order->status, 'to' => $validated['status']]];
            $order->status = $validated['status'];
            $order->save();

            $order->audits()->create([
                'actor' => OrderUpdate::ACTOR_STAFF,
                'action' => 'status_changed',
                'changes' => $changes,
            ]);
        });

        return $this->successResponse(new AdminOrderResource($order->refresh()), 'Order updated');
    }
}
