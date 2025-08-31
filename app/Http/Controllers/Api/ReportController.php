<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\StoreReportRequest;
use App\Http\Resources\ApiResource;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request): ApiResource
    {
        $validated = $request->validated();

        $existingReport = Report::query()
            ->where('created_by', Auth::id())
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
            'created_by' => Auth::id(),
            'reportable_type' => $validated['reportable_type'],
            'reportable_id' => $validated['reportable_id'],
            'reason' => $validated['reason'],
            'additional_info' => $validated['additional_info'],
            'status' => 'pending',
        ]);

        return ApiResource::created(
            resource: $report,
            message: 'Report submitted successfully. Thank you for helping keep our community safe.'
        );
    }
}
