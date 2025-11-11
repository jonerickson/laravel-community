<x-mail::message>
# New Support Ticket Created

Hello! A new support ticket has been created and requires your attention.

**Ticket Number:** {{ $supportTicket->ticket_number }}<br />
**Subject:** {{ $supportTicket->subject }}<br />
**Priority:** {{ $supportTicket->priority->getLabel() }}<br />
**Status:** {{ $supportTicket->status->getLabel() }}<br />
**Category:** {{ $supportTicket->category->name ?? 'Not specified' }}<br />
**Created By:** {{ $supportTicket->author->name }}<br />
**Created At:** {{ $supportTicket->created_at->format('M j, Y \a\t g:i A') }}<br />

<x-mail::panel>
{!! $supportTicket->description !!}
</x-mail::panel>

<x-mail::button :url="route('support.show', $supportTicket->reference_id)">
View ticket
</x-mail::button>

Thanks,<br />
{{ config('app.name') }}
</x-mail::message>
