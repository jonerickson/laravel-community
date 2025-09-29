<x-mail::message>
# Subscription Created

Hello {{ $order->user->name }},

Your subscription **#{{ $order->reference_id }}** has been successfully created! Welcome to our community.

## Subscription Details

**Order Number:** {{ $order->reference_id }}<br>
**Invoice Number:** {{ $order->invoice_number }}<br>
**Status:** {{ $order->status->getLabel() }}<br>
**Total:** ${{ number_format($order->amount / 100, 2) }}<br>

<x-mail::table>
| Item | Price |
|:-----|------:|
@foreach($order->items as $item)
| {{ $item->product?->name ?? 'Unknown Product' }} | {{ $item->price?->getLabel() ?? 'N/A' }} |
@endforeach
</x-mail::table>

<x-mail::button :url="route('settings.orders')">
View subscription
</x-mail::button>

Your subscription is now active and you can begin enjoying all the benefits. If you have any questions, our support team is here to help.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
