import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { FormEvent } from 'react';
import { Link, useSearchParams } from 'react-router';
import type { Product } from '../../shared/api/types';
import { formatMoney } from '../../shared/money';
import { EmptyState, ErrorAlert, Loading } from '../../shared/ui/feedback';
import { Pagination } from '../../shared/ui/Pagination';
import { adminProductsQuery, deleteProduct } from './api';

export function AdminProductsPage() {
  const [params, setParams] = useSearchParams();
  const q = params.get('q') ?? '';
  const page = Number(params.get('page') ?? 1);

  const queryClient = useQueryClient();
  const products = useQuery(adminProductsQuery(q, page));

  const remove = useMutation({
    mutationFn: (product: Product) => deleteProduct(product.id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['products'] }),
  });

  const onSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const term = String(new FormData(event.currentTarget).get('q') ?? '').trim();
    setParams(term === '' ? {} : { q: term });
  };

  const confirmDelete = (product: Product) => {
    if (
      window.confirm(`Delete "${product.name}" (${product.sku})? It will disappear from the store.`)
    ) {
      remove.mutate(product);
    }
  };

  return (
    <section className="flex flex-col gap-4">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-xl font-semibold">Products</h1>
        <Link to="/admin/products/new" className="btn-primary">
          New product
        </Link>
      </header>

      <form role="search" onSubmit={onSearch} className="flex gap-2" key={q}>
        <input
          name="q"
          type="search"
          defaultValue={q}
          aria-label="Search products"
          placeholder="Search by name, SKU or description"
          className="input"
        />
        <button type="submit" className="btn-secondary">
          Search
        </button>
      </form>

      {remove.isError && <ErrorAlert error={remove.error} />}
      {products.isPending && <Loading />}
      {products.isError && <ErrorAlert error={products.error} />}
      {products.data?.data.length === 0 && <EmptyState>No products found.</EmptyState>}

      {products.data && products.data.data.length > 0 && (
        <div className="card overflow-x-auto p-0">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-gray-200 bg-gray-50 text-xs text-gray-500 uppercase">
              <tr>
                <th className="px-4 py-2">SKU</th>
                <th className="px-4 py-2">Name</th>
                <th className="px-4 py-2">Category</th>
                <th className="px-4 py-2 text-right">Price</th>
                <th className="px-4 py-2 text-right">Stock</th>
                <th className="px-4 py-2">
                  <span className="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {products.data.data.map((product) => (
                <tr key={product.id}>
                  <td className="px-4 py-2 font-mono text-xs">{product.sku}</td>
                  <td className="px-4 py-2">{product.name}</td>
                  <td className="px-4 py-2 text-gray-600">{product.category.name}</td>
                  <td className="px-4 py-2 text-right">{formatMoney(product.price)}</td>
                  <td className="px-4 py-2 text-right">{product.stock}</td>
                  <td className="px-4 py-2 text-right whitespace-nowrap">
                    <Link
                      to={`/admin/products/${product.id}/edit`}
                      className="btn-secondary"
                      aria-label={`Edit ${product.name}`}
                    >
                      Edit
                    </Link>
                    <button
                      type="button"
                      className="btn-danger ml-2"
                      disabled={remove.isPending}
                      aria-label={`Delete ${product.name}`}
                      onClick={() => confirmDelete(product)}
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {products.data && (
        <Pagination
          meta={products.data.meta}
          onChange={(next) => setParams({ ...(q ? { q } : {}), page: String(next) })}
        />
      )}
    </section>
  );
}
