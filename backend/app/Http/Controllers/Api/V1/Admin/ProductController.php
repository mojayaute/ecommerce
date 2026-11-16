<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Catalog\CreateProduct;
use App\Domain\Catalog\DeleteProduct;
use App\Domain\Catalog\SearchProducts;
use App\Domain\Catalog\UpdateProduct;
use App\Http\Requests\Admin\SaveProductRequest;
use App\Http\Requests\SearchProductsRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class ProductController
{
    public function index(SearchProductsRequest $request, SearchProducts $searchProducts): AnonymousResourceCollection
    {
        return ProductResource::collection($searchProducts->handle($request->toCriteria()));
    }

    public function store(SaveProductRequest $request, CreateProduct $createProduct): ProductResource
    {
        return new ProductResource($createProduct->handle($request->toData()));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category'));
    }

    public function update(SaveProductRequest $request, Product $product, UpdateProduct $updateProduct): ProductResource
    {
        return new ProductResource($updateProduct->handle($product, $request->toData()));
    }

    public function destroy(Product $product, DeleteProduct $deleteProduct): Response
    {
        $deleteProduct->handle($product);

        return response()->noContent();
    }
}
