import { keepPreviousData, queryOptions } from '@tanstack/react-query';
import { api, withQuery } from '../../shared/api/client';
import type { Paginated, Product, Resource } from '../../shared/api/types';

export type ProductPayload = {
  sku: string;
  name: string;
  description: string | null;
  category: string;
  price: string;
  stock: number;
  weight_kg: string;
};

export const adminProductsQuery = (q: string, page: number) =>
  queryOptions({
    queryKey: ['products', 'admin', { q, page }],
    queryFn: () =>
      api.get<Paginated<Product>>(withQuery('/admin/products', { q, page, sort: 'name' })),
    placeholderData: keepPreviousData,
  });

export const adminProductQuery = (id: number) =>
  queryOptions({
    queryKey: ['products', 'admin', 'detail', id],
    queryFn: async () => (await api.get<Resource<Product>>(`/admin/products/${id}`)).data,
  });

export const saveProduct = (id: number | null, payload: ProductPayload) =>
  id === null
    ? api.post<Resource<Product>>('/admin/products', payload)
    : api.put<Resource<Product>>(`/admin/products/${id}`, payload);

export const deleteProduct = (id: number) => api.delete(`/admin/products/${id}`);
