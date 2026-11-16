<?php

declare(strict_types=1);

namespace App\Domain\Ordering;

enum OrderStatus: string
{
    case Paid = 'paid';
}
