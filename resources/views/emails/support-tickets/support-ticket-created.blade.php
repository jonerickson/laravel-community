{{ \App\Services\EmailParserService::DELIMITER }}<br>
----------------------------------<br>
A new support ticket has been created. Please see below for more details.<br>
<br>
<x-mail::message>
# New Support Ticket Created

Hello! A new support ticket has been created and requires your attention.

**Ticket Number:** {{ $supportTicket->ticket_number }}<br />
**Subject:** {{ $supportTicket->subject }}<br />
**Status:** {{ $supportTicket->status->getLabel() }}<br />
**Priority:** {{ $supportTicket->priority->getLabel() }}<br />
**Category:** {{ $supportTicket->category->name ?? 'Not specified' }}<br />

<x-mail::panel>
**{{ $supportTicket->author->name }}** at {{ $supportTicket->created_at->format('M j, Y \a\t g:i A') }}:<br />
{!! $supportTicket->description !!}
</x-mail::panel>

<x-mail::button :url="route('support.show', $supportTicket->reference_id)">
View ticket
</x-mail::button>

Thanks,<br />
{{ config('app.name') }}
</x-mail::message>
