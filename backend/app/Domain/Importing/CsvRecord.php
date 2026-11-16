<?php

declare(strict_types=1);

namespace App\Domain\Importing;

final readonly class CsvRecord
{
    /** @param list<string> $values */
    public function __construct(
        public int $line,
        public array $values,
    ) {}

    public function isBlank(): bool
    {
        return implode('', array_map(trim(...), $this->values)) === '';
    }
}
