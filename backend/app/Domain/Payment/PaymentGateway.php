<?php

declare(strict_types=1);

namespace App\Domain\Payment;

interface PaymentGateway
{
    /** @throws PaymentDeclined */
    public function charge(Charge $charge): PaymentReceipt;
}
