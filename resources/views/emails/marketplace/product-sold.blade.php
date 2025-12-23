<x-mail::message>
# Your Product Has Been Sold!

Hello {{ $seller->name }},

Great news! Your product has been purchased in order **#{{ $order->reference_id }}**.

**Order ID:** {{ $order->reference_id }}<br>
**Customer:** {{ $order->user->name }}<br>
**Order Date:** {{ $order->created_at->format('F j, Y') }}<br>

@if(count($items))
<x-mail::table>
| Product | Quantity | Sale Amount |
|:--------|:--------:|-----------:|
@foreach($items as $item)
| {{ $item->price->product->name }} | {{ $item->quantity }} | {{ \Illuminate\Support\Number::currency($item->amount) }}
@endforeach
</x-mail::table>

**Total Commission:** {{ \Illuminate\Support\Number::currency($order->commission_amount) }}
@endif

<x-mail::button :url="route('filament.marketplace.pages.dashboard')">
View dashboard
</x-mail::button>

Thank you for being part of our marketplace! Keep up the great work.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
