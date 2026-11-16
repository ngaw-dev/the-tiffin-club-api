<?php

use App\Models\Order;

/** @var Order $order */
?>
<x-mail::message>
# Your tiffin order is confirmed

Hi {{ $order->customer->name }}, here's what's coming:

@foreach ($order->items as $item)
- {{ $item->quantity }} × {{ $item->menuItem?->name }} — ${{ number_format($item->quantity * $item->unit_price / 100, 2) }}
@endforeach

**Total: ${{ number_format($order->total / 100, 2) }}** — pay in person.

@if ($order->fulfilment === \App\Models\Order::FULFILMENT_DELIVERY)
**Delivery** to {{ $order->customer->address }}{{ $order->customer->suburb ? ', '.$order->customer->suburb : '' }}.
@else
**Pickup** — collect during today's pickup window from {{ \App\Models\GlobalSetting::get('pickup_address') }}.
@endif

You can change or cancel this order until the day's cutoff. Manage your order with the link below — anyone with it can edit or cancel, so keep it to yourself.

<x-mail::button :url="url('/orders/'.$order->manage_token)">
Manage your order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
