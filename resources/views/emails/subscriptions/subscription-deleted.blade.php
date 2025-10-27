<x-mail::message>
# Subscription Cancelled

Hello {{ $user->name }},

Your subscription **{{ $product->name }}** has been cancelled. You may re-subscribe at anytime. We're sorry to see you go!

**Subscription:** {{ $product->name }}

<x-mail::button :url="route('settings.orders')">
View order history
</x-mail::button>

If this cancellation was unexpected or if you'd like to resubscribe in the future, our support team is here to help. We'd love to have you back!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
