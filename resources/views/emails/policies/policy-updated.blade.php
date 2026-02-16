<x-mail::message>
# Policy updated

The following policy has been updated on {{ config('app.name') }}.

**Policy:** {{ $policy->title }}<br />
@if ($policy->version)
**Version:** {{ $policy->version }}<br />
@endif
@if ($policy->description)
**Description:** {{ $policy->description }}<br />
@endif

<x-mail::button :url="route('policies.show', [$policy->category->slug, $policy->slug])">
View policy
</x-mail::button>

Please review the updated policy at your earliest convenience.

Thanks,
<br />
{{ config('app.name') }}
</x-mail::message>