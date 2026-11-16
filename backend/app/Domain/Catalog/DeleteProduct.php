<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Models\Product;

final class DeleteProduct
{
    public function handle(Product $product): void
    {
        $product->delete();
    }
}
