<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Stripe;

use App\Data\DiscountData;
use Spatie\LaravelData\Normalizers\Normalized\Normalized;
use Spatie\LaravelData\Normalizers\Normalizer;
use Stripe\Invoice;

class InvoiceNormalizer implements Normalizer
{
    public function normalize(mixed $value): null|array|Normalized
    {
        if ($value instanceof Invoice) {
            return [
                'id' => $value->id,
                'amount' => $value->total,
                'invoice_url' => $value->hosted_invoice_url,
                'invoice_pdf_url' => $value->invoice_pdf,
                'external_payment_id' => $value->payments->data[0]->id ?? null,
                'discounts' => DiscountData::collect($value->discounts),
            ];
        }

        return null;
    }
}
