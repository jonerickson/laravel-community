<x-mail::message>
# New Report Submitted

A new report has been submitted by **{{ $report->author?->name ?? 'A user' }}**.

**Reportable Type:** {{ class_basename($report->reportable_type) }}<br>
**Reason:** {{ $report->reason->getLabel() }}<br>
@if(filled($report->additional_info))
**Additional Info:** {{ $report->additional_info }}<br>
@endif
**Submitted At:** {{ $report->created_at->format('M d, Y \a\t g:i A') }}<br>

@if($content = $report->getContent())
<x-mail::panel>
{!! $content !!}
</x-mail::panel>
@endif

<x-mail::button :url="url('/admin/reports')">
Review reports
</x-mail::button>

Please review this report as soon as possible.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
