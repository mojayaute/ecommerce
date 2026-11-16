<?php

declare(strict_types=1);

namespace App\Domain\Importing;

final class RowResult
{
    private bool $skipped = false;

    private ?ImportRowStatus $savedAs = null;

    private ?int $productId = null;

    /**
     * @param  array<int|string, string>  $rawData
     * @param  array<string, string>  $values
     * @param  list<RowIssue>  $issues
     */
    public function __construct(
        public readonly int $line,
        public readonly array $rawData,
        public readonly array $values = [],
        private array $issues = [],
    ) {}

    public function sku(): ?string
    {
        $sku = $this->values['sku'] ?? '';

        return $sku === '' ? null : $sku;
    }

    public function fingerprint(): string
    {
        return json_encode($this->values, JSON_THROW_ON_ERROR);
    }

    public function reject(RowIssue $issue): void
    {
        $this->issues[] = $issue;
    }

    public function skip(RowIssue $issue): void
    {
        $this->issues[] = $issue;
        $this->skipped = true;
    }

    public function saved(ImportRowStatus $status, int $productId): void
    {
        $this->savedAs = $status;
        $this->productId = $productId;
    }

    public function isImportable(): bool
    {
        return ! $this->skipped && ! $this->hasErrors();
    }

    public function status(): ImportRowStatus
    {
        return match (true) {
            $this->skipped => ImportRowStatus::Skipped,
            $this->hasErrors() || $this->savedAs === null => ImportRowStatus::Rejected,
            default => $this->savedAs,
        };
    }

    public function productId(): ?int
    {
        return $this->productId;
    }

    /** @return list<array{field: string|null, severity: string, message: string}> */
    public function issues(): array
    {
        return array_map(static fn (RowIssue $issue): array => $issue->toArray(), $this->issues);
    }

    private function hasErrors(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->severity === IssueSeverity::Error) {
                return true;
            }
        }

        return false;
    }
}
