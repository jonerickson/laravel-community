<x-mail::message>
# Warning Issued

You have been issued a warning on {{ config('app.name') }}.

**Warning Type:** {{ $userWarning->warning->name }}<br />
**Points:** {{ $userWarning->warning->points }}<br />
**Points Expire:** {{ $userWarning->points_expire_at->format('F j, Y') }}<br />
@if ($userWarning->reason)
**Reason:** {{ $userWarning->reason }}<br />
@endif

@if ($userWarning->warningConsequence)
**{{ $userWarning->warningConsequence->type->getLabel() }}:** {{ $userWarning->warningConsequence->type->getDescription() }}<br />
**Restriction Expires:** {{ $userWarning->consequence_expires_at->format('F j, Y') }}<br />
@endif

Please review our community guidelines to avoid further warnings.

Thanks,
<br />
{{ config('app.name') }}
</x-mail::message>
