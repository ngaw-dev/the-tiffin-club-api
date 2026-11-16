{{-- resources/views/staff/payments.blade.php --}}
@extends('staff.layout')

@section('title', 'Payments')

@section('content')
<header class="page-head">
    <h1>Payments</h1>
</header>
<p>
    Paid: ${{ number_format($totalPaid / 100, 2) }} · Outstanding: ${{ number_format($totalOutstanding / 100, 2) }}
</p>

<h2>By customer</h2>
<table>
    <tr>
        <th>Customer</th><th>Orders</th><th>Due</th><th>Paid</th><th>Balance</th><th>Outstanding orders</th>
    </tr>
    @forelse ($customers as $row)
        @php($customer = $row['customer'])
        <tr>
            <td>{{ $customer->name }}<br><small>{{ $customer->email }}</small></td>
            <td>{{ $row['orderCount'] }}</td>
            <td>${{ number_format($row['due'] / 100, 2) }}</td>
            <td>${{ number_format($row['paid'] / 100, 2) }}</td>
            <td>
                @if ($row['outstanding'] > 0)
                    <span class="badge-owes">Owes: ${{ number_format($row['outstanding'] / 100, 2) }}</span>
                @elseif ($row['outstanding'] < 0)
                    <span class="badge-credit">Credit: ${{ number_format(-$row['outstanding'] / 100, 2) }}</span>
                @else
                    $0.00
                @endif
            </td>
            <td>
                @foreach ($row['unpaidOrders'] as $order)
                    @php($paid = $order->payments->sum('amount'))
                    <div style="margin-bottom: .4rem;">
                        #{{ $order->id }} · ${{ number_format(($order->total - $paid) / 100, 2) }} left
                        <form class="inline" method="POST" action="{{ route('staff.orders.pay', $order) }}">
                            @csrf
                            <input type="number" name="amount" step="0.01" min="0.01" max="{{ ($order->total - $paid) / 100 }}" placeholder="Amount" required>
                            <button type="submit">Payment done</button>
                        </form>
                    </div>
                @endforeach
            </td>
        </tr>
    @empty
        <tr><td colspan="6">No customers with orders</td></tr>
    @endforelse
</table>

<h2>Payment history</h2>
<table>
    <tr>
        <th>Date</th><th>Customer</th><th>Order</th><th>Amount</th><th>Method</th>
    </tr>
    @forelse ($payments as $payment)
        <tr>
            <td>{{ $payment->paid_at->format('d M H:i') }}</td>
            <td>{{ $payment->customer->name }}</td>
            <td>#{{ $payment->order_id }}</td>
            <td class="paid-full">${{ number_format($payment->amount / 100, 2) }}</td>
            <td>{{ $payment->method }}</td>
        </tr>
    @empty
        <tr><td colspan="5">No payments recorded</td></tr>
    @endforelse
</table>
@endsection
