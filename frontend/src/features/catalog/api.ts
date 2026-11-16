import { keepPreviousData, queryOptions } from '@tanstack/react-query';
import { api, withQuery } from '../../shared/api/client';
import type { Category, Paginated, Product, Resource } from '../../shared/api/types';

export type ProductSort = 'relevance' | 'name' | 'price_asc' | 'price_desc';

export type ProductFilters = {
  q?: string;
  category_id?: string;
  min_price?: string;
  max_price?: string;
  in_stock?: boolean;
  sort?: ProductSort;
  page?: number;
};

export const productsQuery = (filters: ProductFilters) =>
  queryOptions({
    queryKey: ['products', 'list', filters],
    queryFn: () => api.get<Paginated<Product>>(withQuery('/products', filters)),
    placeholderData: keepPreviousData,
  });

export const productQuery = (id: number) =>
  queryOptions({
    queryKey: ['products', 'detail', id],
    queryFn: async () => (await api.get<Resource<Product>>(`/products/${id}`)).data,
  });

export const categoriesQuery = () =>
  queryOptions({
    queryKey: ['categories'],
    queryFn: async () => (await api.get<{ data: Category[] }>('/categories')).data,
    staleTime: 60_000,
  });
