<x-mail::message>
# Your Gift Card is Ready!

Hello {{ $user->name }},

Great news! You've received a gift card worth **{{ $balance }}**.

**Gift Card Code:** `{{ $code }}`<br>
**Balance:** {{ $balance }}<br>
**Status:** Active<br>

## How to Use Your Gift Card

To redeem your gift card, simply:
1. Add items to your cart
2. Proceed to checkout
3. Enter the gift card code
4. Your discount will be applied automatically

Your gift card can be used multiple times until the balance is fully depleted. You can check your remaining balance in your account settings.

<x-mail::button :url="route('store.index')">
Start shopping
</x-mail::button>

**Important:** Keep this code safe and do not share it with anyone you don't trust, as it can be used by anyone who has it.

If you have any questions or need assistance, please don't hesitate to contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
