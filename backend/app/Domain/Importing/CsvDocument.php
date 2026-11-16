<?php

declare(strict_types=1);

namespace App\Domain\Importing;

final readonly class CsvDocument
{
    /**
     * @param  list<string>  $headers
     * @param  list<CsvRecord>  $records
     */
    public function __construct(
        public array $headers,
        public array $records,
    ) {}
}
