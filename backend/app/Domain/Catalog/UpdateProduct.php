<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Models\Product;
use Illuminate\Database\ConnectionInterface;

final readonly class UpdateProduct
{
    public function __construct(
        private ConnectionInterface $database,
        private ResolveCategory $resolveCategory,
    ) {}

    public function handle(Product $product, ProductData $data): Product
    {
        return $this->database->transaction(function () use ($product, $data): Product {
            $category = $this->resolveCategory->handle($data->categoryName);

            $product->update($data->toAttributes($category->id));

            return $product->setRelation('category', $category);
        });
    }
}
