<?php

declare(strict_types=1);

namespace App\Domain\Importing;

use App\Domain\Catalog\Sku;

final class RowNormalizer
{
    /** @param array<string, string> $fields */
    public function normalize(array $fields): NormalizedRow
    {
        $values = array_map(trim(...), $fields);
        $warnings = [];

        $values['sku'] = Sku::normalize($values['sku']);

        if (str_starts_with($values['price'], '$')) {
            $warnings[] = RowIssue::warning('price', "Removed the currency symbol from \"{$values['price']}\".");
            $values['price'] = ltrim(substr($values['price'], 1));
        }

        return new NormalizedRow($values, $warnings);
    }
}
