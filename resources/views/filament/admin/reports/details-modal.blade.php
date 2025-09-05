<div class="space-y-6">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Reporter Information</h3>
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
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Reported Content</h3>
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
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Report Details</h3>
        <div class="space-y-3">
            <div>
                <span class="text-sm text-gray-600 dark:text-gray-400">Reason:</span>
                <div class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $record->reason->getColor() }}-100 text-{{ $record->reason->getColor() }}-800 dark:bg-{{ $record->reason->getColor() }}-900 dark:text-{{ $record->reason->getColor() }}-200">
                        {{ $record->reason->getLabel() }}
                    </span>
                </div>
            </div>

            @if($record->additional_info)
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Additional Information:</span>
                    <div class="mt-1 p-3 bg-white dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">
                        <p class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $record->additional_info }}</p>
                    </div>
                </div>
            @else
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Additional Information:</span>
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic mt-1">No additional information provided</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Review Information (if reviewed) --}}
    @if($record->reviewed_at)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Review Information</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $record->status->getColor() }}-100 text-{{ $record->status->getColor() }}-800 dark:bg-{{ $record->status->getColor() }}-900 dark:text-{{ $record->status->getColor() }}-200">
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
                @if($record->admin_notes)
                    <div class="mt-3">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Admin Notes:</span>
                        <div class="mt-1 p-3 bg-white dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">
                            <p class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $record->admin_notes }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
