<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

final class Sku
{
    public const PATTERN = '/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/';

    public static function normalize(string $value): string
    {
        return mb_strtoupper(trim($value));
    }
}
