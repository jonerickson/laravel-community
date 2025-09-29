<x-mail::message>
# Order Completed Successfully

Hello {{ $order->user->name }},

Excellent! Your order **#{{ $order->reference_id }}** has been completed successfully. Thank you for your business!

## Order Details

**Order ID:** {{ $order->reference_id }}<br>
**Invoice Number:** {{ $order->invoice_number }}<br>
**Status:** {{ $order->status->getLabel() }}<br>
**Total:** ${{ number_format($order->amount / 100, 2) }}<br>

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
View order
</x-mail::button>

We hope you enjoy your purchase! If you have any questions or concerns, please don't hesitate to contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
