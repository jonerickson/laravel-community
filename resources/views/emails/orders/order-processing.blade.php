<x-mail::message>
# Order Processing

Hello {{ $order->user->name }},

Great news! Your order **#{{ $order->reference_id }}** is now being processed. We're preparing your items for shipment.

**Order ID:** {{ $order->reference_id }}<br>
**Invoice Number:** {{ $order->invoice_number }}<br>
**Status:** {{ $order->status->getLabel() }}<br>
**Total:** {{ \Illuminate\Support\Number::currency($order->amount) }}<br>

@if(count($order->items))
<x-mail::table>
| Item | Quantity |
|:-----|---------:|
@foreach($order->items as $item)
| {{ $item->name }} | {{ $item->quantity }} |
@endforeach
</x-mail::table>
@endif

<x-mail::button :url="route('settings.orders')">
View order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
