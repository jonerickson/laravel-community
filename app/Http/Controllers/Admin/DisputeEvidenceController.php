<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Policies\GenerateDisputeEvidenceAction;
use App\Models\Order;

class DisputeEvidenceController
{
    public function __invoke(Order $order): mixed
    {
        return GenerateDisputeEvidenceAction::execute($order);
    }
}
