import type { Product } from '../shared/api/types';

export function aProduct(overrides: Partial<Product> = {}): Product {
  return {
    id: 3,
    sku: 'WM-042',
    name: 'Wireless Mouse',
    description: 'Ergonomic wireless mouse with USB receiver',
    category: { id: 1, name: 'Electronics' },
    price: '29.99',
    stock: 75,
    weight_kg: '0.120',
    created_at: null,
    updated_at: null,
    ...overrides,
  };
}
