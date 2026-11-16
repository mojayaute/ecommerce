import type { APIRequestContext } from '@playwright/test';

export type CreatedProduct = { id: number; sku: string; name: string };

export function uniqueSku(prefix: string): string {
  return `E2E-${prefix}-${Date.now().toString(36).toUpperCase()}`;
}

export async function createProduct(
  request: APIRequestContext,
  overrides: { name: string; price: string; stock: number },
): Promise<CreatedProduct> {
  const sku = uniqueSku(overrides.name.replace(/\W+/g, '').slice(0, 6).toUpperCase());
  const response = await request.post('/api/v1/admin/products', {
    headers: { Accept: 'application/json' },
    data: { sku, category: 'E2E', weight_kg: '0.5', description: null, ...overrides },
  });

  if (!response.ok()) {
    throw new Error(`Could not create ${sku}: ${response.status()} ${await response.text()}`);
  }

  const { data } = (await response.json()) as { data: CreatedProduct };

  return data;
}

export async function deleteProduct(request: APIRequestContext, product: CreatedProduct) {
  await request.delete(`/api/v1/admin/products/${product.id}`, {
    headers: { Accept: 'application/json' },
  });
}
