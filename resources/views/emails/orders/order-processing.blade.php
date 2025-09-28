<x-mail::message>
# Order Processing

Hello {{ $order->user->name }},

Great news! Your order **#{{ $order->reference_id }}** is now being processed. We're preparing your items for shipment.

## Order Details

**Order ID:** {{ $order->reference_id }}<br>
**Invoice Number:** {{ $order->invoice_number }}<br>
**Status:** {{ $order->status->getLabel() }}<br>
**Total:** ${{ number_format($order->amount / 100, 2) }}<br>

## Items Ordered

<x-mail::table>
| Item | Quantity | Price |
|:-----|---------:|------:|
@foreach($order->items as $item)
| {{ $item->product?->name ?? 'Unknown Product' }} | {{ $item->quantity }} | {{ $item->price?->getLabel() ?? 'N/A' }} |
@endforeach
</x-mail::table>

<x-mail::button :url="route('settings.orders')">
View order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
