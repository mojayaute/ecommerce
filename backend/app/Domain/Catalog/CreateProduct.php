<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Models\Product;
use Illuminate\Database\ConnectionInterface;

final readonly class CreateProduct
{
    public function __construct(
        private ConnectionInterface $database,
        private ResolveCategory $resolveCategory,
    ) {}

    public function handle(ProductData $data): Product
    {
        return $this->database->transaction(function () use ($data): Product {
            $category = $this->resolveCategory->handle($data->categoryName);

            $product = Product::query()->create($data->toAttributes($category->id));

            return $product->setRelation('category', $category);
        });
    }
}
