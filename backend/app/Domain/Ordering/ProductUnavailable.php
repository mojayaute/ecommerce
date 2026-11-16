<?php

declare(strict_types=1);

namespace App\Domain\Ordering;

use App\Domain\DomainException;

final class ProductUnavailable extends DomainException
{
    /** @param list<int> $productIds */
    public function __construct(
        private readonly array $productIds,
    ) {
        parent::__construct('Some products in the cart are no longer available.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'product_unavailable';
    }

    public function details(): array
    {
        return ['product_ids' => $this->productIds];
    }
}
