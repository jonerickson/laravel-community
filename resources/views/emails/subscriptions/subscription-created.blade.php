<x-mail::message>
# Subscription Created

Hello {{ $order->user->name }},

Your subscription **#{{ $order->reference_id }}** has been successfully created! Welcome to our community.

**Order Number:** {{ $order->reference_id }}<br>
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
View subscription
</x-mail::button>

Your subscription is now active and you can begin enjoying all the benefits. If you have any questions, our support team is here to help.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
