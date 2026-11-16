{{-- resources/views/staff/orders.blade.php --}}
@extends('staff.layout')

@section('title', 'Orders')

@section('content')
<header class="page-head">
    <h1>Orders</h1>
</header>
<p>
    @foreach ($stats as $status => $count)
        <strong>{{ str_replace('_', ' ', $status) }}: {{ $count }}</strong>@if (! $loop->last) · @endif
    @endforeach
    <br>
    Paid: ${{ number_format($totalPaid / 100, 2) }} · Outstanding (excl. cancelled): ${{ number_format($totalOutstanding / 100, 2) }}
</p>
<nav class="page">
    <a href="{{ route('staff.orders', ['fulfilment' => 'delivery']) }}" @class(['active' => request('fulfilment') === 'delivery'])>Delivery ({{ $orders->where('fulfilment', 'delivery')->count() }})</a>
    <a href="{{ route('staff.orders', ['fulfilment' => 'pickup']) }}" @class(['active' => request('fulfilment') === 'pickup'])>Pickup ({{ $orders->where('fulfilment', 'pickup')->count() }})</a>
</nav>
<table>
    <tr>
        <th>Placed</th><th>Customer</th><th>Items</th><th>Total</th><th>Paid</th><th>Status</th><th>Actions</th>
    </tr>
    @forelse ($orders->where('fulfilment', request('fulfilment', 'delivery')) as $order)
        @php($paid = $order->payments->sum('amount'))
        <tr>
            <td>{{ $order->placed_at->format('d M H:i') }}</td>
            <td>{{ $order->customer->name }}<br><small>{{ $order->customer->email }}</small></td>
            <td>
                @foreach ($order->items as $item)
                    {{ $item->quantity }}× {{ $item->menuItem->name }}@if (! $loop->last), @endif
                @endforeach
            </td>
            <td>${{ number_format($order->total / 100, 2) }}</td>
            <td class="{{ $paid >= $order->total && $order->total > 0 ? 'paid-full' : ($paid > 0 ? 'paid-partial' : '') }}">
                ${{ number_format($paid / 100, 2) }}
            </td>
            <td>{{ str_replace('_', ' ', $order->status) }}</td>
            <td>
                @php($options = $order->fulfilment === \App\Models\Order::FULFILMENT_PICKUP
                    ? [\App\Models\Order::STATUS_CONFIRMED, \App\Models\Order::STATUS_READY_FOR_PICKUP, \App\Models\Order::STATUS_COMPLETED, \App\Models\Order::STATUS_CANCELLED]
                    : [\App\Models\Order::STATUS_CONFIRMED, \App\Models\Order::STATUS_OUT_FOR_DELIVERY, \App\Models\Order::STATUS_COMPLETED, \App\Models\Order::STATUS_CANCELLED])
                <form class="inline" method="POST" action="{{ route('staff.orders.status', $order) }}">
                    @csrf @method('PATCH')
                    <select name="status">
                        @foreach ($options as $status)
                            <option value="{{ $status }}" @selected($order->status === $status)>{{ str_replace('_', ' ', $status) }}</option>
                        @endforeach
                    </select>
                    <button type="submit">Set</button>
                </form>
                @if ($paid < $order->total)
                    <form class="inline" method="POST" action="{{ route('staff.orders.pay', $order) }}">
                        @csrf
                        <input type="number" name="amount" step="0.01" min="0.01" max="{{ $order->total / 100 }}" placeholder="Amount" required>
                        <button type="submit">Payment done</button>
                    </form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="7">No orders</td></tr>
    @endforelse
</table>
@endsection
