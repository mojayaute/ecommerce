<?php

declare(strict_types=1);

namespace App\Domain\Ordering;

use App\Domain\DomainException;

final class PriceChanged extends DomainException
{
    public function __construct(
        private readonly string $currentTotal,
    ) {
        parent::__construct("The order total is now {$currentTotal}. Review the cart before paying.");
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'price_changed';
    }

    public function details(): array
    {
        return ['current_total' => $this->currentTotal];
    }
}
