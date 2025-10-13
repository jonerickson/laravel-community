<x-mail::message>
# Subscription Updated

Hello {{ $order->user->name }},

Your subscription **#{{ $order->reference_id }}** has been updated.

@switch($newStatus)
@case(\App\Enums\SubscriptionStatus::Active)
  🎉 Great news! Your subscription is now **active** and all features are available.
  @break
@case(\App\Enums\SubscriptionStatus::Cancelled)
  ❌ Your subscription has been **cancelled**. You can resubscribe at any time.
  @break
@case(\App\Enums\SubscriptionStatus::Trialing)
  🆓 You're in a **trial period**. Enjoy exploring all the features!
  @break
@case(\App\Enums\SubscriptionStatus::PastDue)
  ⚠️ Your subscription is **past due**. Please update your payment method to avoid service interruption.
  @break
@case(\App\Enums\SubscriptionStatus::Unpaid)
  💳 Your subscription is **unpaid**. Please complete payment to continue your subscription.
  @break
@case(\App\Enums\SubscriptionStatus::Incomplete)
  🔄 Your subscription setup is **incomplete**. Please complete the payment process.
  @break
@case(\App\Enums\SubscriptionStatus::IncompleteExpired)
  ⏰ Your subscription setup has **expired**. Please start the subscription process again.
  @break
@default
  ℹ️ Your subscription status has been updated.
@endswitch

**Order Number:** {{ $order->reference_id }}<br />
**Invoice Number:** {{ $order->invoice_number }}<br />
**Current Status:** {{ $newStatus?->getLabel() ?? $order->status->getLabel() }}<br />
**Total:** {{ \Illuminate\Support\Number::currency($order->amount) }}<br />

@if(count($order->items))
<x-mail::table>
| Item | Quantity |
|:-----|---------:|
@foreach($order->items as $item)
| {{ $item->name }} | {{ $item->quantity }} |
@endforeach
</x-mail::table>
@endif

<x-mail::button :url="route('store.subscriptions')">Manage subscription</x-mail::button>

@if ($newStatus && in_array($newStatus, [\App\Enums\SubscriptionStatus::PastDue, \App\Enums\SubscriptionStatus::Unpaid, \App\Enums\SubscriptionStatus::Incomplete]))
If you need assistance with payment or have any questions, our support team is here to help.
@else
If you have any questions about this status change, our support team is here to help.
@endif

Thanks,
<br />
{{ config('app.name') }}
</x-mail::message>
