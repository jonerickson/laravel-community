<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Actions\Action;
use App\Data\DisputeEvidenceData;
use App\Models\Order;
use Spatie\LaravelPdf\Facades\Pdf;

class GenerateDisputeEvidenceAction extends Action
{
    public function __construct(
        protected Order $order,
    ) {}

    public function __invoke(): mixed
    {
        $data = DisputeEvidenceData::fromOrder($this->order);

        return Pdf::view('pdf.dispute-evidence', ['data' => $data])
            ->format('a4')
            ->name('dispute-evidence-'.$this->order->reference_id.'-'.now()->format('Y-m-d').'.pdf');
    }
}
