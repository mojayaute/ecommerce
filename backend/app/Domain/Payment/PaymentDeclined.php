<?php

declare(strict_types=1);

namespace App\Domain\Payment;

use App\Domain\DomainException;

final class PaymentDeclined extends DomainException
{
    public function __construct()
    {
        parent::__construct('The payment was declined. No charge was made.');
    }

    public function status(): int
    {
        return 402;
    }

    public function errorCode(): string
    {
        return 'payment_declined';
    }
}
