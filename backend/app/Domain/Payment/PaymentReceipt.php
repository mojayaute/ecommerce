<?php

declare(strict_types=1);

namespace App\Domain\Payment;

final readonly class PaymentReceipt
{
    public function __construct(
        public string $reference,
    ) {}
}
