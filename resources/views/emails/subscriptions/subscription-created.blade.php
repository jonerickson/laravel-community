<x-mail::message>
# Subscription Started

Hello {{ $user->name }},

Your subscription **{{ $product->name }}** has been successfully started! Welcome to our community.

**Subscription:** {{ $product->name }}

<x-mail::button :url="route('store.subscriptions')">
Manage subscription
</x-mail::button>

Your subscription is now active and you can begin enjoying all the benefits. If you have any questions, our support team is here to help.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
