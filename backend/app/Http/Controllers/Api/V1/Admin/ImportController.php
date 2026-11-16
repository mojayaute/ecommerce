<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Importing\ImportProducts;
use App\Domain\Importing\ImportRowStatus;
use App\Domain\Importing\InvalidCsvFile;
use App\Http\Requests\Admin\ImportProductsRequest;
use App\Http\Resources\ImportBatchResource;
use App\Http\Resources\ImportRowResource;
use App\Models\ImportBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ImportController
{
    private const PER_PAGE = 20;

    private const ROWS_PER_PAGE = 50;

    public function index(): AnonymousResourceCollection
    {
        return ImportBatchResource::collection(ImportBatch::query()->latest('id')->paginate(self::PER_PAGE));
    }

    public function store(ImportProductsRequest $request, ImportProducts $importProducts): ImportBatchResource
    {
        $file = $request->upload();

        try {
            $batch = $importProducts->handle($file->getRealPath(), $file->getClientOriginalName());
        } catch (InvalidCsvFile $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        return new ImportBatchResource($batch);
    }

    public function show(ImportBatch $import): ImportBatchResource
    {
        return new ImportBatchResource($import);
    }

    public function rows(Request $request, ImportBatch $import): AnonymousResourceCollection
    {
        $request->validate(['status' => ['nullable', Rule::enum(ImportRowStatus::class)]]);

        $rows = $import->rows()
            ->when($request->enum('status', ImportRowStatus::class), fn ($query, ImportRowStatus $status) => $query->where('status', $status))
            ->orderBy('line_number')
            ->paginate(self::ROWS_PER_PAGE)
            ->withQueryString();

        return ImportRowResource::collection($rows);
    }
}
