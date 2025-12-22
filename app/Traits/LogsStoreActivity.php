<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait LogsStoreActivity
{
    protected function logMarketplaceActivity(string $description, ?array $properties = null): void
    {
        activity('marketplace')
            ->causedBy(Auth::user())
            ->performedOn($this)
            ->withProperties($properties ?? [])
            ->log($description);
    }

    protected function logPurchase(float $amount, string $currency = 'USD'): void
    {
        $this->logMarketplaceActivity('Product purchased', [
            'amount' => $amount,
            'currency' => $currency,
            'product_id' => $this->id ?? null,
            'product_name' => $this->name ?? null,
        ]);
    }

    protected function logDownload(): void
    {
        $this->logMarketplaceActivity('Product downloaded', [
            'product_id' => $this->id ?? null,
            'product_name' => $this->name ?? null,
        ]);
    }

    protected function logReview(int $rating, ?string $comment = null): void
    {
        $this->logMarketplaceActivity('Product reviewed', [
            'rating' => $rating,
            'comment' => $comment,
            'product_id' => $this->id ?? null,
            'product_name' => $this->name ?? null,
        ]);
    }

    protected function logWishlist(bool $added = true): void
    {
        $action = $added ? 'added to' : 'removed from';
        $this->logMarketplaceActivity(sprintf('Product %s wishlist', $action), [
            'action' => $added ? 'added' : 'removed',
            'product_id' => $this->id ?? null,
            'product_name' => $this->name ?? null,
        ]);
    }
}
