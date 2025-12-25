<x-mail::message>
# Support Ticket Not Found

We received your email but were unable to find the support ticket you referenced: **{{ $ticketNumber }}**

This may have happened because:
- The ticket number in your email subject was modified or removed
- The ticket has been deleted
- The ticket number is incorrect

## What To Do Next

To respond to an existing support ticket, please ensure the ticket number in your email subject line remains unchanged (e.g., "ST-12345").

Alternatively, you can log in to your dashboard to view and manage your support tickets directly.

<x-mail::button :url="route('support.index')">
View my tickets
</x-mail::button>

If you continue to experience issues, please create a new support ticket through your dashboard.

Thanks,<br />
{{ config('app.name') }}
</x-mail::message>
