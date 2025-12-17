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
                'invoiceUrl' => $value->hosted_invoice_url,
                'invoicePdfUrl' => $value->invoice_pdf,
                'externalOrderId' => data_get($value, 'payments.data.0.payment.payment_intent.id'),
                'externalPaymentId' => data_get($value, 'payments.data.0.payment.payment_intent.payment_method'),
                'discounts' => DiscountData::collect($value->discounts),
            ];
        }

        return null;
    }
}
