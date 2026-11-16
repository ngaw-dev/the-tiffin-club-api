<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminPaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PaymentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $payments = QueryBuilder::for(Payment::class)
            ->allowedFilters(
                'method',
                AllowedFilter::exact('order_id'),
                AllowedFilter::exact('customer_id'),
            )
            ->allowedSorts('paid_at', 'amount')
            ->defaultSort('-paid_at')
            ->with(['customer', 'order'])
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return $this->paginatedResponse(AdminPaymentResource::collection($payments), 'Payments');
    }

    /**
     * Record a manual (cash/offline) payment against an order, mirroring
     * the staff panel's settle workflow.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $order = Order::query()->findOrFail($validated['order_id']);

        if ($order->total - $order->payments()->sum('amount') < $validated['amount'] * 100) {
            return $this->errorResponse(errors: 'Amount exceeds the order\'s outstanding balance', message: 'Payment rejected', statusCode: 422);
        }

        $payment = $order->payments()->create([
            'customer_id' => $order->customer_id,
            'amount' => (int) round($validated['amount'] * 100),
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        return $this->successResponse(new AdminPaymentResource($payment->load('customer')), 'Payment recorded', 201);
    }
}
