<?php

declare(strict_types=1);

namespace App\Domain\Payment;

use Illuminate\Support\Str;

final class FakePaymentGateway implements PaymentGateway
{
    private const DECLINED_EMAIL = 'decline@example.com';

    public function charge(Charge $charge): PaymentReceipt
    {
        if (strcasecmp($charge->customerEmail, self::DECLINED_EMAIL) === 0) {
            throw new PaymentDeclined;
        }

        return new PaymentReceipt('fake_'.Str::lower(Str::random(24)));
    }
}
