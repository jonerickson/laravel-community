<x-mail::message>
# Your Promo Code is Ready!

Hello {{ $user->name }},

Great news! You've received a promo code worth **{{ $discountValue }}** off your next purchase.

**Promo Code:** `{{ $code }}`<br>
**Discount:** {{ $discountValue }}<br>
@if($expiresAt)
**Expires:** {{ $expiresAt }}<br>
@else
**Expires:** Never<br>
@endif

## How to Use Your Promo Code

To redeem your promo code, simply:
1. Add items to your cart
2. Proceed to checkout
3. Enter the promo code: `{{ $code }}`
4. Your discount will be applied automatically

@if($promoCode->max_uses)
This promo code can be used {{ $promoCode->max_uses }} time{{ $promoCode->max_uses > 1 ? 's' : '' }}.
@endif

<x-mail::button :url="route('store.index')">
Start shopping
</x-mail::button>

**Important:** Keep this code safe and do not share it with anyone you don't trust, as it can be used by anyone who has it.

If you have any questions or need assistance, please don't hesitate to contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
