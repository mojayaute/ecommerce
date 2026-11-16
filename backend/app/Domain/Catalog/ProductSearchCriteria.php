<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

final readonly class ProductSearchCriteria
{
    public function __construct(
        public ?string $term = null,
        public ?int $categoryId = null,
        public ?string $minPrice = null,
        public ?string $maxPrice = null,
        public bool $inStockOnly = false,
        public ?ProductSort $sort = null,
    ) {}

    public function effectiveSort(): ProductSort
    {
        return $this->sort ?? ($this->term === null ? ProductSort::Name : ProductSort::Relevance);
    }
}
