<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\StoreReportRequest;
use App\Http\Resources\ApiResource;
use App\Models\Report;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        #[CurrentUser]
        private readonly User $user,
    ) {
        //
    }

    public function store(StoreReportRequest $request): ApiResource
    {
        $existingReport = Report::query()
            ->whereBelongsTo($this->user, 'author')
            ->where('reportable_type', $request->validated('reportable_type'))
            ->where('reportable_id', $request->validated('reportable_type'))
            ->exists();

        if ($existingReport) {
            return ApiResource::error(
                message: 'You have already reported this content.',
                errors: ['duplicate' => 'Report already exists for this content'],
                status: 400
            );
        }

        $report = Report::create([
            'reportable_type' => $request->validated('reportable_type'),
            'reportable_id' => $request->validated('reportable_id'),
            'reason' => $request->validated('reason'),
            'additional_info' => $request->validated('additional_info'),
            'status' => 'pending',
        ]);

        return ApiResource::created(
            resource: $report,
            message: 'Your report was submitted successfully. Thank you for helping keep our community safe.'
        );
    }
}
