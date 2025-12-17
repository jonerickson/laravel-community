<?php

declare(strict_types=1);

use App\Models\Discount;
use App\Services\DiscountService;

test('it can validate a discount', function (): void {
    $discount = Discount::factory()->create();

    $this->instance(DiscountService::class, Mockery::mock(DiscountService::class, function (Mockery\MockInterface $mock): void {
        $mock->shouldReceive('validateDiscount')->andReturnNull();
    }));
});
