<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

final readonly class ProductData
{
    public function __construct(
        public string $sku,
        public string $name,
        public ?string $description,
        public string $categoryName,
        public string $price,
        public int $stock,
        public string $weightKg,
    ) {}

    /** @return array<string, mixed> */
    public function toAttributes(int $categoryId): array
    {
        return [
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $categoryId,
            'price' => $this->price,
            'stock' => $this->stock,
            'weight_kg' => $this->weightKg,
        ];
    }
}
