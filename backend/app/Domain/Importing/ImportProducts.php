<?php

declare(strict_types=1);

namespace App\Domain\Importing;

use App\Domain\Catalog\CreateProduct;
use App\Domain\Catalog\ProductData;
use App\Domain\Catalog\ProductRules;
use App\Domain\Catalog\UpdateProduct;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Product;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Database\ConnectionInterface;

final readonly class ImportProducts
{
    private const COLUMNS = ['name', 'sku', 'description', 'category', 'price', 'stock', 'weight_kg'];

    private const CHUNK_SIZE = 500;

    public function __construct(
        private ConnectionInterface $database,
        private ValidatorFactory $validator,
        private CsvReader $reader,
        private RowNormalizer $normalizer,
        private FlagDuplicateSkus $flagDuplicateSkus,
        private CreateProduct $createProduct,
        private UpdateProduct $updateProduct,
    ) {}

    public function handle(string $path, string $originalFilename): ImportBatch
    {
        $document = $this->reader->read($path);

        $missing = array_values(array_diff(self::COLUMNS, $document->headers));

        if ($missing !== []) {
            throw InvalidCsvFile::missingColumns($missing);
        }

        $rows = array_map(fn (CsvRecord $record): RowResult => $this->assess($record, $document->headers), $document->records);

        $this->flagDuplicateSkus->handle($rows);

        return $this->database->transaction(fn (): ImportBatch => $this->persist($rows, $originalFilename));
    }

    /** @param list<string> $headers */
    private function assess(CsvRecord $record, array $headers): RowResult
    {
        if ($record->isBlank()) {
            $row = new RowResult($record->line, $record->values);
            $row->skip(RowIssue::warning(null, 'The row is empty.'));

            return $row;
        }

        if (count($record->values) !== count($headers)) {
            return new RowResult($record->line, $record->values, issues: [
                RowIssue::error(null, sprintf('Expected %d values but found %d.', count($headers), count($record->values))),
            ]);
        }

        $rawData = array_combine($headers, $record->values);
        $normalized = $this->normalizer->normalize(array_intersect_key($rawData, array_flip(self::COLUMNS)));

        $issues = $normalized->warnings;

        foreach ($this->validator->make($normalized->values, ProductRules::fields())->errors()->messages() as $field => $messages) {
            foreach ($messages as $message) {
                $issues[] = RowIssue::error($field, $message);
            }
        }

        return new RowResult($record->line, $rawData, $normalized->values, $issues);
    }

    /** @param list<RowResult> $rows */
    private function persist(array $rows, string $originalFilename): ImportBatch
    {
        $batch = ImportBatch::query()->create(['original_filename' => mb_substr($originalFilename, 0, 255)]);

        $existing = $this->existingProducts($rows);

        foreach ($rows as $row) {
            $sku = $row->sku();

            if ($row->isImportable() && $sku !== null) {
                $this->save($row, $existing[$sku] ?? null);
            }
        }

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            ImportRow::query()->insert(array_map(static fn (RowResult $row): array => [
                'import_batch_id' => $batch->id,
                'line_number' => $row->line,
                'raw_data' => json_encode($row->rawData, JSON_THROW_ON_ERROR),
                'status' => $row->status()->value,
                'issues' => json_encode($row->issues(), JSON_THROW_ON_ERROR),
                'product_id' => $row->productId(),
            ], $chunk));
        }

        $counts = array_count_values(array_map(static fn (RowResult $row): string => $row->status()->value, $rows));

        $batch->update([
            'total_rows' => count($rows),
            'created_count' => $counts[ImportRowStatus::Created->value] ?? 0,
            'updated_count' => $counts[ImportRowStatus::Updated->value] ?? 0,
            'rejected_count' => $counts[ImportRowStatus::Rejected->value] ?? 0,
            'skipped_count' => $counts[ImportRowStatus::Skipped->value] ?? 0,
        ]);

        return $batch;
    }

    /**
     * @param  list<RowResult>  $rows
     * @return array<string, Product>
     */
    private function existingProducts(array $rows): array
    {
        $skus = [];

        foreach ($rows as $row) {
            $sku = $row->sku();

            if ($row->isImportable() && $sku !== null) {
                $skus[$sku] = $sku;
            }
        }

        $products = [];

        foreach (array_chunk(array_values($skus), 1000) as $chunk) {
            foreach (Product::withTrashed()->whereIn('sku', $chunk)->get() as $product) {
                $products[$product->sku] = $product;
            }
        }

        return $products;
    }

    private function save(RowResult $row, ?Product $existing): void
    {
        if ($existing?->trashed()) {
            $row->reject(RowIssue::error('sku', 'The SKU belongs to a deleted product.'));

            return;
        }

        $data = $this->productData($row->values);

        if ($existing !== null) {
            $this->updateProduct->handle($existing, $data);
            $row->saved(ImportRowStatus::Updated, $existing->id);

            return;
        }

        $row->saved(ImportRowStatus::Created, $this->createProduct->handle($data)->id);
    }

    /** @param array<string, string> $values */
    private function productData(array $values): ProductData
    {
        return new ProductData(
            sku: $values['sku'],
            name: $values['name'],
            description: $values['description'] === '' ? null : $values['description'],
            categoryName: $values['category'],
            price: $values['price'],
            stock: (int) $values['stock'],
            weightKg: $values['weight_kg'],
        );
    }
}
