<x-mail::message>
# Order Cancelled

Hello {{ $order->user->name }},

Your order **#{{ $order->reference_id }}** has been cancelled. If you have any questions about this cancellation, please contact our support team.

## Order Details

**Order Number:** {{ $order->reference_id }}<br>
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

If this cancellation was unexpected or if you need assistance with placing a new order, our support team is here to help.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
