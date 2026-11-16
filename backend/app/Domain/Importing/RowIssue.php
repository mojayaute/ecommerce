<?php

declare(strict_types=1);

namespace App\Domain\Importing;

final readonly class RowIssue
{
    public function __construct(
        public ?string $field,
        public IssueSeverity $severity,
        public string $message,
    ) {}

    public static function error(?string $field, string $message): self
    {
        return new self($field, IssueSeverity::Error, $message);
    }

    public static function warning(?string $field, string $message): self
    {
        return new self($field, IssueSeverity::Warning, $message);
    }

    /** @return array{field: string|null, severity: string, message: string} */
    public function toArray(): array
    {
        return ['field' => $this->field, 'severity' => $this->severity->value, 'message' => $this->message];
    }
}
