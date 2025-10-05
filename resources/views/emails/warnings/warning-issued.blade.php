<x-mail::message>
    # Warning Issued You have been issued a warning on {{ config('app.name') }}. ## Warning Details **Warning Type:**
    {{ $userWarning->warning->name }}
    <br />
    **Points:** {{ $userWarning->warning->points }}
    <br />
    **Expires:** {{ $userWarning->expires_at->format('F j, Y') }}
    <br />
    @if ($userWarning->reason)
        **Reason:** {{ $userWarning->reason }}
        <br />
    @endif

    **Your current warning points:** {{ $userWarning->points_at_issue }}

    @if ($user->active_consequence)
        ## Current Restriction

        **{{ $user->active_consequence->type->getLabel() }}**

        {{ $user->active_consequence->type->getDescription() }}
    @endif

    Please review our community guidelines to avoid further warnings.

    Thanks,
    <br />
    {{ config('app.name') }}
</x-mail::message>