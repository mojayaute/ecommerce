import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import { mockApi, renderRoute } from '../../test/utils';

const categories = { 'GET /api/v1/categories': { body: { data: [] } } };

describe('product form', () => {
  it('validates the fields before calling the API', async () => {
    const fetchMock = mockApi(categories);
    const user = userEvent.setup();

    renderRoute('/admin/products/new');
    await user.click(await screen.findByRole('button', { name: 'Save product' }));

    expect(await screen.findByText('Enter a SKU.')).toBeInTheDocument();
    expect(screen.getByText('Enter a price like 19.99.')).toBeInTheDocument();
    expect(fetchMock.mock.calls.every(([url]) => String(url).endsWith('/categories'))).toBe(true);
  });

  it('shows the errors returned by the server next to each field', async () => {
    mockApi({
      ...categories,
      'POST /api/v1/admin/products': {
        status: 422,
        body: {
          message: 'The sku has already been taken.',
          errors: { sku: ['The sku has already been taken.'] },
        },
      },
    });
    const user = userEvent.setup();

    renderRoute('/admin/products/new');
    await user.type(await screen.findByLabelText('SKU'), 'RS-001');
    await user.type(screen.getByLabelText('Name'), 'Running Shoes');
    await user.type(screen.getByLabelText('Category'), 'Footwear');
    await user.type(screen.getByLabelText('Price (USD)'), '89.99');
    await user.clear(screen.getByLabelText('Stock'));
    await user.type(screen.getByLabelText('Stock'), '10');
    await user.type(screen.getByLabelText('Weight (kg)'), '0.35');
    await user.click(screen.getByRole('button', { name: 'Save product' }));

    expect(await screen.findByText('The sku has already been taken.')).toBeInTheDocument();
    expect(screen.getByLabelText('SKU')).toHaveAttribute('aria-invalid', 'true');
  });
});
