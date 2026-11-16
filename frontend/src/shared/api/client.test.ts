import { describe, expect, it } from 'vitest';
import { mockApi } from '../../test/utils';
import { api, ApiError, withQuery } from './client';

describe('api client', () => {
  it('turns error responses into an ApiError with code, details and field errors', async () => {
    mockApi({
      'POST /api/v1/orders': {
        status: 409,
        body: {
          message: 'Only 5 units of Running Shoes are available.',
          code: 'insufficient_stock',
          details: { available: 5 },
          errors: { 'customer.email': ['The email is invalid.'] },
        },
      },
    });

    const error = await api.post('/orders', {}).catch((caught: unknown) => caught);

    expect(error).toBeInstanceOf(ApiError);
    expect(error).toMatchObject({
      status: 409,
      code: 'insufficient_stock',
      message: 'Only 5 units of Running Shoes are available.',
      details: { available: 5 },
    });
    expect((error as ApiError).firstError('customer.email')).toBe('The email is invalid.');
  });

  it('builds query strings without empty filters', () => {
    expect(withQuery('/products', { q: 'mouse', category_id: '', in_stock: false, page: 2 })).toBe(
      '/products?q=mouse&page=2',
    );
  });
});
