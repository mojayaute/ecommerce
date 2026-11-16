<?php

declare(strict_types=1);

namespace App\Domain\Importing;

final class CsvReader
{
    private const BOM = "\u{FEFF}";

    public function read(string $path): CsvDocument
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw InvalidCsvFile::unreadable();
        }

        if (str_starts_with($contents, self::BOM)) {
            $contents = substr($contents, strlen(self::BOM));
        }

        if (trim($contents) === '') {
            throw InvalidCsvFile::empty();
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            throw InvalidCsvFile::notUtf8();
        }

        return $this->parse($contents);
    }

    private function parse(string $contents): CsvDocument
    {
        $stream = fopen('php://memory', 'r+');

        if ($stream === false) {
            throw InvalidCsvFile::unreadable();
        }

        fwrite($stream, $contents);
        rewind($stream);

        $headers = null;
        $records = [];
        $line = 1;
        $offset = 0;

        while (($values = fgetcsv($stream, escape: '')) !== false) {
            $values = array_map(static fn (?string $value): string => $value ?? '', $values);
            $startLine = $line;

            $position = (int) ftell($stream);
            $line += substr_count($contents, "\n", $offset, $position - $offset);
            $offset = $position;

            if ($headers === null) {
                $headers = array_map(static fn (string $header): string => mb_strtolower(trim($header)), $values);

                continue;
            }

            $records[] = new CsvRecord($startLine, $values);
        }

        fclose($stream);

        return new CsvDocument($headers ?? [], $records);
    }
}
