<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderUpdate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::query()
            ->with(['customer', 'payments'])
            ->latest('placed_at')
            ->get();

        $statuses = [
            Order::STATUS_CONFIRMED,
            Order::STATUS_READY_FOR_PICKUP,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ];

        $stats = collect($statuses)
            ->mapWithKeys(fn ($status): array => [
                $status => $orders->where('status', $status)->count(),
            ]);

        return view('staff.orders', [
            'orders' => $orders,
            'stats' => $stats,
            'totalPaid' => $orders->sum(fn ($order) => $order->payments->sum('amount')),
            'totalOutstanding' => $orders->reject(fn ($order) => $order->isCancelled())
                ->sum(fn ($order) => $order->total - $order->payments->sum('amount')),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
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

        DB::transaction(function () use ($order, $validated): void {
            $changes = $order->status !== $validated['status']
                ? ['status' => ['from' => $order->status, 'to' => $validated['status']]]
                : null;
            $order->status = $validated['status'];
            $order->save();

            $order->audits()->create([
                'actor' => OrderUpdate::ACTOR_STAFF,
                'action' => 'status_changed',
                'changes' => $changes,
            ]);
        });

        return redirect('/staff/orders');
    }

    public function pay(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$order->total / 100],
        ]);

        DB::transaction(function () use ($order, $validated): void {
            $order->payments()->create([
                'customer_id' => $order->customer_id,
                'amount' => (int) round($validated['amount'] * 100),
                'method' => 'cash',
                'paid_at' => now(),
            ]);
        });

        return redirect('/staff/orders');
    }
}
