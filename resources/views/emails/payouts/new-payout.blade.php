<x-mail::message>
# You've Been Paid!

Hello {{ $payout->seller->name }},

Exciting news! A new payout has been initiated for your marketplace earnings.

**Payout ID:** {{ $payout->reference_id }}<br>
**Amount:** {{ \Illuminate\Support\Number::currency($payout->amount) }}<br>
**Status:** {{ $payout->status->getLabel() }}<br>
@if($payout->payout_method)
**Payment Method:** {{ $payout->payout_method->getLabel() }}<br>
@endif
**Created:** {{ $payout->created_at->format('F j, Y \a\t g:i A') }}<br>

@if($payout->notes)
**Notes:** {!! $payout->notes !!}
@endif

@if($payout->commissions->count() > 0)
This payout includes **{{ $payout->commissions->count() }}** {{ Str::plural('commission', $payout->commissions->count()) }} from your recent sales.
@endif

<x-mail::button :url="route('filament.marketplace.pages.dashboard')">
View marketplace dashboard
</x-mail::button>

Your payout is being processed and will be sent to your account shortly. Thank you for being a valued seller in our marketplace!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
