<x-mail::message>
# Payment Action Required

Hello {{ $order->user->name }},

Your payment for order **#{{ $order->reference_id }}** requires additional verification to complete the transaction.

## Order Details

**Order Number:** {{ $order->reference_id }}<br>
**Invoice Number:** {{ $order->invoice_number }}<br>
**Status:** {{ $order->status->getLabel() }}<br>
**Amount:** ${{ number_format($order->amount / 100, 2) }}<br>

@if(count($order->items))
<x-mail::table>
| Item | Quantity |
|:-----|---------:|
@foreach($order->items as $item)
| {{ $item->getLabel() }} | {{ $item->quantity }} |
@endforeach
</x-mail::table>
@endif

<x-mail::button :url="$confirmationUrl">
Complete payment verification
</x-mail::button>

**Important:** This payment verification must be completed within the next 24 hours, or your order may be automatically cancelled.

This additional step is required by your bank or payment provider to ensure the security of your transaction. Once you complete the verification, your order will be processed immediately.

If you have any questions or need assistance, our support team is here to help.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
