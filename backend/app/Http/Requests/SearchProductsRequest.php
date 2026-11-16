<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Catalog\ProductSearchCriteria;
use App\Domain\Catalog\ProductSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SearchProductsRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $maxPrice = ['nullable', 'numeric', 'min:0'];

        if (is_numeric($this->input('min_price'))) {
            $maxPrice[] = 'gte:min_price';
        }

        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => $maxPrice,
            'in_stock' => ['nullable', Rule::in(['0', '1', 'true', 'false'])],
            'sort' => ['nullable', Rule::enum(ProductSort::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toCriteria(): ProductSearchCriteria
    {
        return new ProductSearchCriteria(
            term: $this->filled('q') ? $this->string('q')->toString() : null,
            categoryId: $this->filled('category_id') ? $this->integer('category_id') : null,
            minPrice: $this->filled('min_price') ? $this->string('min_price')->toString() : null,
            maxPrice: $this->filled('max_price') ? $this->string('max_price')->toString() : null,
            inStockOnly: $this->boolean('in_stock'),
            sort: $this->enum('sort', ProductSort::class),
        );
    }
}
