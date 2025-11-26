<x-mail::message>
# Refund Processed

Hello {{ $order->user->name }},

Your refund for order **#{{ $order->reference_id }}** has been successfully processed.

**Order Number:** {{ $order->reference_id }}<br>
@if($order->invoice_number)
**Invoice Number:** {{ $order->invoice_number }}<br>
@endif
**Status:** {{ $order->status->getLabel() }}<br>
**Refund Amount:** {{ \Illuminate\Support\Number::currency($order->amount) }}<br>
**Refund Reason:** {{ $reason->getLabel() }}<br>

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
View order details
</x-mail::button>

@switch($reason)
@case(\App\Enums\OrderRefundReason::Duplicate)
This refund was processed because a duplicate payment was detected.
@break
@case(\App\Enums\OrderRefundReason::Fraudulent)
This refund was processed due to fraudulent activity detection.
@break
@case(\App\Enums\OrderRefundReason::RequestedByCustomer)
This refund was processed at your request.
@break
@default
This refund has been processed for your order.
@endswitch

The refund amount will be returned to your original payment method within 5-10 business days. If you have any questions about this refund, our support team is here to help.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
