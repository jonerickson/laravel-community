<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\StoreReportRequest;
use App\Http\Resources\ApiResource;
use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
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

    /**
     * @throws AuthorizationException
     */
    public function store(StoreReportRequest $request): ApiResource
    {
        $validated = $request->validated();

        $existingReport = Report::query()
            ->whereBelongsTo($this->user, 'author')
            ->where('reportable_type', $validated['reportable_type'])
            ->where('reportable_id', $validated['reportable_id'])
            ->exists();

        if ($existingReport) {
            return ApiResource::error(
                message: 'You have already reported this content.',
                errors: ['duplicate' => 'Report already exists for this content'],
                status: 400
            );
        }

        $report = Report::create([
            'reportable_type' => $validated['reportable_type'],
            'reportable_id' => $validated['reportable_id'],
            'reason' => $validated['reason'],
            'additional_info' => $validated['additional_info'],
            'status' => 'pending',
        ]);

        return ApiResource::created(
            resource: $report,
            message: 'Your report was submitted successfully. Thank you for helping keep our community safe.'
        );
    }
}
