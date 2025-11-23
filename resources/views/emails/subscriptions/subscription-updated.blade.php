<x-mail::message>
# Subscription Updated

Hello {{ $user->name }},

Your subscription **{{ $product->name }}** has been updated.

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
  ⚠️ Your subscription is **past due**. Please pay any open invoice or update your payment method to continue your subscription.
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

<x-mail::button :url="route('store.subscriptions')">
    Manage subscription
</x-mail::button>

@if ($newStatus && in_array($newStatus, [\App\Enums\SubscriptionStatus::PastDue, \App\Enums\SubscriptionStatus::Unpaid, \App\Enums\SubscriptionStatus::Incomplete]))
If you need assistance with payment or have any questions, our support team is here to help.
@else
If you have any questions about this status change, our support team is here to help.
@endif

Thanks,
<br />
{{ config('app.name') }}
</x-mail::message>
