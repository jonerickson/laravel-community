<x-mail::message>
# Subscription Cancelled

Hello {{ $order->user->name }},

Your subscription **#{{ $order->reference_id }}** has been cancelled. You may re-subscribe at anytime. We're sorry to see you go!

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
View order history
</x-mail::button>

If this cancellation was unexpected or if you'd like to resubscribe in the future, our support team is here to help. We'd love to have you back!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
