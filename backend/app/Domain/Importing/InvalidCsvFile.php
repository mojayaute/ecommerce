<?php

declare(strict_types=1);

namespace App\Domain\Importing;

use RuntimeException;

final class InvalidCsvFile extends RuntimeException
{
    public static function unreadable(): self
    {
        return new self('The file could not be read.');
    }

    public static function empty(): self
    {
        return new self('The file is empty.');
    }

    public static function notUtf8(): self
    {
        return new self('The file must be UTF-8 encoded.');
    }

    /** @param list<string> $columns */
    public static function missingColumns(array $columns): self
    {
        return new self('The file is missing the columns: '.implode(', ', $columns).'.');
    }
}
