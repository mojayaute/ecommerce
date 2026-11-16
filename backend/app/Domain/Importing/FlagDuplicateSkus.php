<?php

declare(strict_types=1);

namespace App\Domain\Importing;

final class FlagDuplicateSkus
{
    /** @param list<RowResult> $rows */
    public function handle(array $rows): void
    {
        foreach ($this->groupBySku($rows) as $sku => $group) {
            $versions = $this->skipExactCopies($group);

            if (count($versions) < 2) {
                continue;
            }

            $lines = implode(', ', array_map(static fn (RowResult $row): int => $row->line, $versions));

            foreach ($versions as $row) {
                $row->reject(RowIssue::error('sku', "SKU {$sku} appears on lines {$lines} with different data."));
            }
        }
    }

    /**
     * @param  list<RowResult>  $rows
     * @return array<string, list<RowResult>>
     */
    private function groupBySku(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $sku = $row->sku();

            if ($sku !== null) {
                $groups[$sku][] = $row;
            }
        }

        return array_filter($groups, static fn (array $group): bool => count($group) > 1);
    }

    /**
     * @param  list<RowResult>  $group
     * @return list<RowResult>
     */
    private function skipExactCopies(array $group): array
    {
        $versions = [];

        foreach ($group as $row) {
            $original = $versions[$row->fingerprint()] ?? null;

            if ($original !== null) {
                $row->skip(RowIssue::warning('sku', "Exact copy of line {$original->line}."));

                continue;
            }

            $versions[$row->fingerprint()] = $row;
        }

        return array_values($versions);
    }
}
