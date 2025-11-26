<x-mail::message>
# Order Processing

Hello {{ $order->user->name }},

Great news! Your order **#{{ $order->reference_id }}** is now being processed. We're preparing your items for shipment.

**Order ID:** {{ $order->reference_id }}<br>
@if($order->invoice_number)
**Invoice Number:** {{ $order->invoice_number }}<br>
@endif
**Status:** {{ $order->status->getLabel() }}<br>
**Total:** {{ \Illuminate\Support\Number::currency($order->amount) }}<br>

@if(count($order->items))
<table class="table" role="presentation" style="width:100%; border-collapse: collapse;">
<thead>
<tr>
<th style="text-align: left;">Item</th>
<th style="text-align: right;">Quantity</th>
<th style="text-align: right;">Subtotal</th>
</tr>
</thead>
<tbody>
@foreach($order->items as $item)
<tr>
<td>{{ $item->name }}</td>
<td style="text-align: right;">{{ $item->quantity }}</td>
<td style="text-align: right;">{{ \Illuminate\Support\Number::currency($item->amount) }}</td>
</tr>
@if($item->description)
<tr>
<td colspan="3" style="padding-top:0px; font-size:12px;">
{{ $item->description }}
</td>
</tr>
@endif
@endforeach
</tbody>
</table>
@endif

<x-mail::button :url="route('settings.orders')">
View order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
