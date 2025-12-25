Please reply above this line.<br>
----------------------------------<br>
<br>
A new comment has been added to your support ticket. Please see below for more details.<br>
<br>
<br>
<x-mail::message>
# New Comment Added

Hello! A new comment has been added to support ticket **{{ $supportTicket->ticket_number }}**.

**Subject:** {{ $supportTicket->subject }}<br />
**Status:** {{ $supportTicket->status->getLabel() }}<br />
**Priority:** {{ $supportTicket->priority->getLabel() }}<br />
**From:** {{ $comment->author->name }}<br />
**Posted:** {{ $comment->created_at->format('M j, Y \a\t g:i A') }}<br />

<x-mail::panel>
{!! $comment->content !!}
</x-mail::panel>

<x-mail::button :url="route('support.show', $supportTicket->reference_id)">
View ticket
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
