<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Importing\ImportRowStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * @property ImportRowStatus $status
 * @property array<int|string, string> $raw_data
 * @property list<array{field: string|null, severity: string, message: string}> $issues
 */
final class ImportRow extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'import_batch_id',
        'line_number',
        'raw_data',
        'status',
        'issues',
        'product_id',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'raw_data' => 'array',
            'status' => ImportRowStatus::class,
            'issues' => 'array',
        ];
    }
}
