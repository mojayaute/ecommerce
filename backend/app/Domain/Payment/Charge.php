<?php

declare(strict_types=1);

namespace App\Domain\Payment;

final readonly class Charge
{
    public function __construct(
        public string $amount,
        public string $currency,
        public string $customerEmail,
        public string $idempotencyKey,
    ) {}
}
