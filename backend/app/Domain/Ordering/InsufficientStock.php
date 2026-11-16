<?php

declare(strict_types=1);

namespace App\Domain\Ordering;

use App\Domain\DomainException;
use App\Models\Product;

final class InsufficientStock extends DomainException
{
    public function __construct(
        private readonly Product $product,
        private readonly int $requested,
    ) {
        parent::__construct("Only {$product->stock} units of {$product->name} are available.");
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'insufficient_stock';
    }

    public function details(): array
    {
        return [
            'product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'requested' => $this->requested,
            'available' => $this->product->stock,
        ];
    }
}
