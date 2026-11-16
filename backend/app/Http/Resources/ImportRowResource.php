<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ImportRow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ImportRowResource extends JsonResource
{
    public function __construct(ImportRow $row)
    {
        parent::__construct($row);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $row = $this->resource;

        return [
            'id' => $row->id,
            'line_number' => $row->line_number,
            'status' => $row->status->value,
            'raw_data' => $row->raw_data,
            'issues' => $row->issues,
            'product_id' => $row->product_id,
        ];
    }
}
