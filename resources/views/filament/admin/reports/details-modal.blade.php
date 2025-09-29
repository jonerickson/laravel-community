<div class="space-y-6">
    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
        <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-gray-100">Reporter Information</h3>
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Name:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->author->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Email:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->author->email }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Reported At:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->created_at->format('M j, Y g:i A') }}</span>
            </div>
        </div>
    </div>

    {{-- Content Information --}}
    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
        <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-gray-100">Reported Content</h3>
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Content Type:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ class_basename($record->reportable_type) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Content ID:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">#{{ $record->reportable_id }}</span>
            </div>
        </div>
    </div>

    {{-- Report Details --}}
    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
        <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-gray-100">Report Details</h3>
        <div class="space-y-3">
            <div>
                <span class="text-sm text-gray-600 dark:text-gray-400">Reason:</span>
                <div class="mt-1">
                    <span
                        class="bg-{{ $record->reason->getColor() }}-100 text-{{ $record->reason->getColor() }}-800 dark:bg-{{ $record->reason->getColor() }}-900 dark:text-{{ $record->reason->getColor() }}-200 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    >
                        {{ $record->reason->getLabel() }}
                    </span>
                </div>
            </div>

            @if ($record->additional_info)
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Additional Information:</span>
                    <div class="mt-1 rounded border border-gray-200 bg-white p-3 dark:border-gray-600 dark:bg-gray-700">
                        <p class="text-sm whitespace-pre-wrap text-gray-900 dark:text-gray-100">{{ $record->additional_info }}</p>
                    </div>
                </div>
            @else
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Additional Information:</span>
                    <p class="mt-1 text-sm text-gray-500 italic dark:text-gray-400">No additional information provided</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Review Information (if reviewed) --}}
    @if ($record->reviewed_at)
        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
            <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-gray-100">Review Information</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                    <span
                        class="bg-{{ $record->status->getColor() }}-100 text-{{ $record->status->getColor() }}-800 dark:bg-{{ $record->status->getColor() }}-900 dark:text-{{ $record->status->getColor() }}-200 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    >
                        {{ $record->status->getLabel() }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Reviewed By:</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->reviewer?->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Reviewed At:</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->reviewed_at->format('M j, Y g:i A') }}</span>
                </div>
                @if ($record->admin_notes)
                    <div class="mt-3">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Admin Notes:</span>
                        <div class="mt-1 rounded border border-gray-200 bg-white p-3 dark:border-gray-600 dark:bg-gray-700">
                            <p class="text-sm whitespace-pre-wrap text-gray-900 dark:text-gray-100">{{ $record->admin_notes }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
