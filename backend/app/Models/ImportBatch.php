<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ImportBatch extends Model
{
    protected $fillable = [
        'original_filename',
        'total_rows',
        'created_count',
        'updated_count',
        'rejected_count',
        'skipped_count',
    ];

    /** @return HasMany<ImportRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'rejected_count' => 'integer',
            'skipped_count' => 'integer',
        ];
    }
}
