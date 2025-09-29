<x-mail::message>
# Payment Successful

Hello {{ $order->user->name }},

Your payment for order **#{{ $order->reference_id }}** has been successfully processed!

## Payment Details

**Order Number:** {{ $order->reference_id }}<br>
**Invoice Number:** {{ $order->invoice_number }}<br>
**Status:** {{ $order->status->getLabel() }}<br>
**Amount Paid:** ${{ number_format($order->amount / 100, 2) }}<br>

@if(count($order->items))
<x-mail::table>
| Item | Quantity |
|:-----|---------:|
@foreach($order->items as $item)
| {{ $item->getLabel() }} | {{ $item->quantity }} |
@endforeach
</x-mail::table>
@endif

<x-mail::button :url="route('settings.orders')">
View order details
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
