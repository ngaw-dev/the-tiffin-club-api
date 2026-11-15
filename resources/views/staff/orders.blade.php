<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orders</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; padding: 1.5rem; background: #f5f5f5; color: #111; }
        table { border-collapse: collapse; width: 100%; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        th, td { text-align: left; padding: .6rem .8rem; border-bottom: 1px solid #eee; font-size: .9rem; }
        form { display: inline-flex; gap: .4rem; align-items: center; margin: 0; }
        select, input, button { font: inherit; padding: .3rem .4rem; }
        button { border: 0; border-radius: 4px; background: #16a34a; color: #fff; cursor: pointer; }
        .paid-full { color: #16a34a; font-weight: 600; }
        .paid-partial { color: #b45309; }
        nav { display: flex; gap: 1rem; margin-bottom: 1rem; }
        nav a { padding: .4rem .8rem; border-radius: 4px; background: #e5e7eb; color: #111; text-decoration: none; }
        nav a.active { background: #16a34a; color: #fff; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    </style>
</head>
<body>
<header>
    <h1>Orders</h1>
    <form method="POST" action="{{ route('staff.logout') }}">
        @csrf
        <button type="submit">Log out</button>
    </form>
</header>
<p>
    @foreach ($stats as $status => $count)
        <strong>{{ str_replace('_', ' ', $status) }}: {{ $count }}</strong>@if (! $loop->last) · @endif
    @endforeach
    <br>
    Paid: ${{ number_format($totalPaid / 100, 2) }} · Outstanding (excl. cancelled): ${{ number_format($totalOutstanding / 100, 2) }}
</p>
<nav>
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
                <form method="POST" action="{{ route('staff.orders.status', $order) }}">
                    @csrf @method('PATCH')
                    <select name="status">
                        @foreach ($options as $status)
                            <option value="{{ $status }}" @selected($order->status === $status)>{{ str_replace('_', ' ', $status) }}</option>
                        @endforeach
                    </select>
                    <button type="submit">Set</button>
                </form>
                @if ($paid < $order->total)
                    <form method="POST" action="{{ route('staff.orders.pay', $order) }}">
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
</body>
</html>
