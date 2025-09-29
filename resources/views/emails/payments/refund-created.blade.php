<x-mail::message>
# Refund Processed

Hello {{ $order->user->name }},

Your refund for order **#{{ $order->reference_id }}** has been successfully processed.

## Refund Details

**Order Number:** {{ $order->reference_id }}<br>
**Invoice Number:** {{ $order->invoice_number }}<br>
**Status:** {{ $order->status->getLabel() }}<br>
**Refund Amount:** ${{ number_format($order->amount / 100, 2) }}<br>
**Refund Reason:** {{ $reason->getLabel() }}<br>

@if(count($order->items))
<x-mail::table>
| Item | Quantity |
|:-----|---------:|
@foreach($order->items as $item)
| {{ $item->getLabel() }} | {{ $item->quantity }} |
@endforeach
</x-mail::table>
@endif

<x-mail::button :url="route('settings.orders')">
View order details
</x-mail::button>

@switch($reason)
@case(\App\Enums\OrderRefundReason::Duplicate)
  This refund was processed because a duplicate payment was detected.
  @break
@case(\App\Enums\OrderRefundReason::Fraudulent)
  This refund was processed due to fraudulent activity detection.
  @break
@case(\App\Enums\OrderRefundReason::RequestedByCustomer)
  This refund was processed at your request.
  @break
@default
  This refund has been processed for your order.
@endswitch

The refund amount will be returned to your original payment method within 5-10 business days. If you have any questions about this refund, our support team is here to help.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
