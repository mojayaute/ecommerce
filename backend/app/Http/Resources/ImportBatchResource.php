<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ImportBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ImportBatchResource extends JsonResource
{
    public function __construct(ImportBatch $batch)
    {
        parent::__construct($batch);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $batch = $this->resource;

        return [
            'id' => $batch->id,
            'original_filename' => $batch->original_filename,
            'total_rows' => $batch->total_rows,
            'created_count' => $batch->created_count,
            'updated_count' => $batch->updated_count,
            'rejected_count' => $batch->rejected_count,
            'skipped_count' => $batch->skipped_count,
            'created_at' => $batch->created_at?->toIso8601String(),
        ];
    }
}
