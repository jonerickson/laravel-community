<x-mail::message>
# Payment Action Required

Hello {{ $order->user->name }},

Your payment for order **#{{ $order->reference_id }}** requires additional verification to complete the transaction.

**Order Number:** {{ $order->reference_id }}<br>
@if($order->invoice_number)
**Invoice Number:** {{ $order->invoice_number }}<br>
@endif
**Status:** {{ $order->status->getLabel() }}<br>
**Amount:** {{ \Illuminate\Support\Number::currency($order->amount) }}<br>

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

<x-mail::button :url="$confirmationUrl">
Complete payment verification
</x-mail::button>

**Important:** This payment verification must be completed within the next 24 hours, or your order may be automatically cancelled.

This additional step is required by your bank or payment provider to ensure the security of your transaction. Once you complete the verification, your order will be processed immediately.

If you have any questions or need assistance, our support team is here to help.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
