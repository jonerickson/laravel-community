<x-mail::message>
# Subscription Cancelled

Hello {{ $order->user->name }},

Your subscription **#{{ $order->reference_id }}** has been cancelled. You may re-subscribe at anytime. We're sorry to see you go!

## Subscription Details

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
View order history
</x-mail::button>

If this cancellation was unexpected or if you'd like to resubscribe in the future, our support team is here to help. We'd love to have you back!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
