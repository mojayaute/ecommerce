<?php

declare(strict_types=1);

namespace App\Domain;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    abstract public function status(): int;

    abstract public function errorCode(): string;

    /** @return array<string, mixed> */
    public function details(): array
    {
        return [];
    }
}
