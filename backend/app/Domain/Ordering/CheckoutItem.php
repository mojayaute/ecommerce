<?php

declare(strict_types=1);

namespace App\Domain\Ordering;

final readonly class CheckoutItem
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}
}
