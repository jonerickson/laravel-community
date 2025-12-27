{{ \App\Services\EmailParserService::DELIMITER }}<br>
----------------------------------<br>
The status of your support ticket has been updated. Please see below for more details.<br>
<br>
<x-mail::message>
# Support Ticket Status Updated

Hello! The status of support ticket **{{ $supportTicket->ticket_number }}** has been updated.

**Subject:** {{ $supportTicket->subject }}<br />
**Status:** {{ $newStatus->getLabel() }}<br />
**Priority:** {{ $supportTicket->priority->getLabel() }}<br />
**Category:** {{ $supportTicket->category->name ?? 'Not specified' }}<br />

Your support ticket has been moved from **{{ $oldStatus->getLabel() }}** to **{{ $newStatus->getLabel() }}**.

@if ($newStatus->value === 'resolved')
Your ticket has been resolved! Please review the solution and let us know if you need any additional assistance.
@elseif ($newStatus->value === 'closed')
Your ticket has been closed. If you have any further questions, please feel free to create a new support ticket.
@elseif ($newStatus->value === 'open')
Your ticket has been re-opened and is now available for review. We'll address your concerns shortly.
@elseif ($newStatus->value === 'in_progress')
Your ticket is now being actively worked on. We'll keep you updated on the progress.
@endif

<x-mail::button :url="route('support.show', $supportTicket->reference_id)">View ticket</x-mail::button>

Thanks,
<br />
{{ config('app.name') }}
</x-mail::message>
