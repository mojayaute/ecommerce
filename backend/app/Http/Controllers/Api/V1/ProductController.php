<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\SearchProducts;
use App\Http\Requests\SearchProductsRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProductController
{
    public function index(SearchProductsRequest $request, SearchProducts $searchProducts): AnonymousResourceCollection
    {
        return ProductResource::collection($searchProducts->handle($request->toCriteria()));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category'));
    }
}
