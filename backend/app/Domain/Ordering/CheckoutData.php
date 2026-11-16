<?php

declare(strict_types=1);

namespace App\Domain\Ordering;

use BcMath\Number;

final readonly class CheckoutData
{
    /** @param list<CheckoutItem> $items */
    public function __construct(
        public string $customerName,
        public string $customerEmail,
        public array $items,
        public Number $expectedTotal,
        public string $idempotencyKey,
    ) {}
}
