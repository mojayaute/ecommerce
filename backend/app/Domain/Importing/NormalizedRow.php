<?php

declare(strict_types=1);

namespace App\Domain\Importing;

final readonly class NormalizedRow
{
    /**
     * @param  array<string, string>  $values
     * @param  list<RowIssue>  $warnings
     */
    public function __construct(
        public array $values,
        public array $warnings,
    ) {}
}
