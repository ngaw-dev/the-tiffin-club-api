<?php

// app/Http/Controllers/Staff/PaymentController.php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $payments = Payment::query()
            ->with(['customer', 'order'])
            ->latest('paid_at')
            ->get();

        // Per-customer summary: cancelled orders are excluded from both due
        // and paid, matching the totals convention on the orders page.
        $customers = Customer::query()
            ->with(['orders.payments'])
            ->get()
            ->map(function (Customer $customer): array {
                $activeOrders = $customer->orders->reject(fn (Order $order) => $order->isCancelled());

                $due = $activeOrders->sum('total');
                $paid = $activeOrders->sum(fn (Order $order) => $order->payments->sum('amount'));

                return [
                    'customer' => $customer,
                    'due' => $due,
                    'paid' => $paid,
                    'outstanding' => $due - $paid,
                    'orderCount' => $activeOrders->count(),
                    // Staff can settle these directly from this page, oldest first.
                    'unpaidOrders' => $activeOrders
                        ->filter(fn (Order $order) => $order->payments->sum('amount') < $order->total)
                        ->sortBy('placed_at')
                        ->values(),
                ];
            })
            // Customers without active orders have nothing to settle; skip them.
            ->filter(fn (array $row): bool => $row['orderCount'] > 0)
            ->sortByDesc('outstanding')
            ->values();

        // Header totals must use the same convention as the summary:
        // payments on cancelled orders count toward neither figure.
        return view('staff.payments', [
            'payments' => $payments,
            'customers' => $customers,
            'totalPaid' => $payments
                ->filter(fn (Payment $payment) => ! $payment->order->isCancelled())
                ->sum('amount'),
            'totalOutstanding' => $customers->sum(fn (array $row) => max($row['outstanding'], 0)),
        ]);
    }
}
