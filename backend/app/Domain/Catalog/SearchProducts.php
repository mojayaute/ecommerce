<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class SearchProducts
{
    private const PER_PAGE = 20;

    /** @return LengthAwarePaginator<int, Product> */
    public function handle(ProductSearchCriteria $criteria): LengthAwarePaginator
    {
        $query = Product::query()->with('category');

        if ($criteria->term !== null) {
            $this->matchTerm($query, $criteria->term);
        }

        $query
            ->when($criteria->categoryId !== null, fn (Builder $q) => $q->where('category_id', $criteria->categoryId))
            ->when($criteria->minPrice !== null, fn (Builder $q) => $q->where('price', '>=', $criteria->minPrice))
            ->when($criteria->maxPrice !== null, fn (Builder $q) => $q->where('price', '<=', $criteria->maxPrice))
            ->when($criteria->inStockOnly, fn (Builder $q) => $q->where('stock', '>', 0));

        $this->applySort($query, $criteria);

        return $query->orderBy('id')->paginate(self::PER_PAGE)->withQueryString();
    }

    /** @param Builder<Product> $query */
    private function matchTerm(Builder $query, string $term): void
    {
        $pattern = '%'.addcslashes($term, '%_\\').'%';

        $query->where(function (Builder $q) use ($term, $pattern): void {
            $q->where('name', 'ilike', $pattern)
                ->orWhere('sku', 'ilike', $pattern)
                ->orWhere('description', 'ilike', $pattern)
                ->orWhereRaw('? <% name', [$term]);
        });
    }

    /** @param Builder<Product> $query */
    private function applySort(Builder $query, ProductSearchCriteria $criteria): void
    {
        match ($criteria->effectiveSort()) {
            ProductSort::Relevance => $query
                ->orderByRaw('CASE WHEN sku = upper(?) THEN 0 ELSE 1 END', [$criteria->term ?? ''])
                ->orderByRaw('word_similarity(?, name) DESC', [$criteria->term ?? ''])
                ->orderBy('name'),
            ProductSort::Name => $query->orderBy('name'),
            ProductSort::PriceAsc => $query->orderBy('price')->orderBy('name'),
            ProductSort::PriceDesc => $query->orderByDesc('price')->orderBy('name'),
        };
    }
}
