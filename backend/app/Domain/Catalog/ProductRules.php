<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

final class ProductRules
{
    /** @return array<string, array<int, mixed>> */
    public static function fields(): array
    {
        return [
            'sku' => ['bail', 'required', 'string', 'max:50', 'regex:'.Sku::PATTERN],
            'name' => ['bail', 'required', 'string', 'max:255', new PlainText],
            'description' => ['bail', 'nullable', 'string', 'max:2000', new PlainText],
            'category' => ['bail', 'required', 'string', 'max:100', new PlainText],
            'price' => ['bail', 'required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'stock' => ['bail', 'required', 'integer', 'min:0', 'max:2147483647'],
            'weight_kg' => ['bail', 'required', 'numeric', 'decimal:0,3', 'gt:0', 'max:99999.999'],
        ];
    }
}
