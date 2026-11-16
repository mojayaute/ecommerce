<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PlainText implements ValidationRule
{
    private const SUSPICIOUS = '/<\/?[a-z!][^>]*>|;\s*--|;\s*(?:drop|delete|insert|update|alter|truncate|create|exec)\b/i';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && preg_match(self::SUSPICIOUS, $value) === 1) {
            $fail('The :attribute field must be plain text without markup or code.');
        }
    }
}
