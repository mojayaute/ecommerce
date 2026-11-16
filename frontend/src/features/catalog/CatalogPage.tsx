import { useQuery } from '@tanstack/react-query';
import type { FormEvent } from 'react';
import { Link, useSearchParams } from 'react-router';
import type { Product } from '../../shared/api/types';
import { formatMoney } from '../../shared/money';
import { EmptyState, ErrorAlert, Loading } from '../../shared/ui/feedback';
import { Pagination } from '../../shared/ui/Pagination';
import { useCartStore } from '../cart/cartStore';
import { useAddToCart } from '../cart/useAddToCart';
import { categoriesQuery, productsQuery, type ProductFilters, type ProductSort } from './api';
import { StockBadge } from './StockBadge';

const SORTS: { value: ProductSort | ''; label: string }[] = [
  { value: '', label: 'Best match' },
  { value: 'name', label: 'Name' },
  { value: 'price_asc', label: 'Price: low to high' },
  { value: 'price_desc', label: 'Price: high to low' },
];

export function CatalogPage() {
  const [params, setParams] = useSearchParams();

  const filters: ProductFilters = {
    q: params.get('q') ?? '',
    category_id: params.get('category_id') ?? '',
    min_price: params.get('min_price') ?? '',
    max_price: params.get('max_price') ?? '',
    in_stock: params.get('in_stock') === 'true',
    sort: (params.get('sort') as ProductSort | null) ?? undefined,
    page: Number(params.get('page') ?? 1),
  };

  const products = useQuery(productsQuery(filters));
  const categories = useQuery(categoriesQuery());

  const update = (changes: Record<string, string>) => {
    const next = new URLSearchParams(params);

    for (const [key, value] of Object.entries(changes)) {
      if (value === '') {
        next.delete(key);
      } else {
        next.set(key, value);
      }
    }

    if (!('page' in changes)) {
      next.delete('page');
    }

    setParams(next);
  };

  const onSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);

    update({
      q: String(form.get('q') ?? '').trim(),
      min_price: String(form.get('min_price') ?? '').trim(),
      max_price: String(form.get('max_price') ?? '').trim(),
    });
  };

  return (
    <div className="flex flex-col gap-6">
      <form
        role="search"
        onSubmit={onSearch}
        className="card grid gap-3 md:grid-cols-[2fr_1fr_1fr_auto]"
        key={params.toString()}
      >
        <input
          name="q"
          type="search"
          aria-label="Search products"
          placeholder="Search by name, SKU or description"
          defaultValue={filters.q}
          className="input"
        />
        <input
          name="min_price"
          inputMode="decimal"
          aria-label="Minimum price"
          placeholder="Min $"
          defaultValue={filters.min_price}
          className="input"
        />
        <input
          name="max_price"
          inputMode="decimal"
          aria-label="Maximum price"
          placeholder="Max $"
          defaultValue={filters.max_price}
          className="input"
        />
        <button type="submit" className="btn-primary">
          Search
        </button>

        <select
          aria-label="Category"
          value={filters.category_id}
          onChange={(event) => update({ category_id: event.target.value })}
          className="input"
        >
          <option value="">All categories</option>
          {categories.data?.map((category) => (
            <option key={category.id} value={category.id}>
              {category.name}
            </option>
          ))}
        </select>
        <select
          aria-label="Sort by"
          value={filters.sort ?? ''}
          onChange={(event) => update({ sort: event.target.value })}
          className="input"
        >
          {SORTS.map((sort) => (
            <option key={sort.value} value={sort.value}>
              {sort.label}
            </option>
          ))}
        </select>
        <label className="flex items-center gap-2 text-sm text-gray-700">
          <input
            type="checkbox"
            checked={filters.in_stock}
            onChange={(event) => update({ in_stock: event.target.checked ? 'true' : '' })}
          />
          In stock only
        </label>
      </form>

      {products.isPending && <Loading />}
      {products.isError && <ErrorAlert error={products.error} />}

      {products.data && products.data.data.length === 0 && (
        <EmptyState>No products match your search.</EmptyState>
      )}

      {products.data && products.data.data.length > 0 && (
        <>
          <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {products.data.data.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </ul>
          <Pagination
            meta={products.data.meta}
            onChange={(page) => update({ page: String(page) })}
          />
        </>
      )}
    </div>
  );
}

function ProductCard({ product }: { product: Product }) {
  const addToCart = useAddToCart();
  const inCart = useCartStore(
    (state) => state.items.find((item) => item.productId === product.id)?.quantity ?? 0,
  );
  const canAdd = inCart < product.stock;

  return (
    <li className="card flex flex-col gap-2">
      <span className="text-xs tracking-wide text-gray-500 uppercase">{product.category.name}</span>
      <Link
        to={`/products/${product.id}`}
        className="font-semibold text-gray-900 hover:text-indigo-700"
      >
        {product.name}
      </Link>
      <span className="text-xs text-gray-400">{product.sku}</span>
      <div className="mt-auto flex items-center justify-between pt-2">
        <span className="text-lg font-semibold">{formatMoney(product.price)}</span>
        <StockBadge stock={product.stock} />
      </div>
      <button
        type="button"
        className="btn-primary"
        disabled={!canAdd}
        onClick={() => addToCart(product)}
      >
        {product.stock === 0 ? 'Out of stock' : canAdd ? 'Add to cart' : 'All units in cart'}
      </button>
    </li>
  );
}
