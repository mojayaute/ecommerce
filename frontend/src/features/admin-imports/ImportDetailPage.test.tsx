import { screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import { mockApi, renderRoute } from '../../test/utils';

const batch = {
  id: 1,
  original_filename: 'products.csv',
  total_rows: 97,
  created_count: 81,
  updated_count: 0,
  rejected_count: 13,
  skipped_count: 3,
  created_at: null,
};

const page = { current_page: 1, last_page: 1, per_page: 50, total: 1, from: 1, to: 1 };

describe('import detail', () => {
  it('lists the rejected rows with their reasons first and filters by status', async () => {
    mockApi({
      'GET /api/v1/admin/imports/1': { body: { data: batch } },
      'GET /api/v1/admin/imports/1/rows?status=rejected&page=1': {
        body: {
          data: [
            {
              id: 6,
              line_number: 7,
              status: 'rejected',
              raw_data: { sku: 'YM-015', name: 'Yoga Mat', price: 'free' },
              issues: [
                { field: 'price', severity: 'error', message: 'The price field must be a number.' },
              ],
              product_id: null,
            },
          ],
          meta: page,
        },
      },
      'GET /api/v1/admin/imports/1/rows?status=skipped&page=1': {
        body: { data: [], meta: { ...page, total: 0 } },
      },
    });

    renderRoute('/admin/imports/1');

    const row = (await screen.findByText('YM-015')).closest('tr');
    expect(row).not.toBeNull();
    expect(
      within(row as HTMLElement).getByText('The price field must be a number.'),
    ).toBeInTheDocument();
    expect(screen.getByRole('tab', { name: /13 rejected/i })).toHaveAttribute(
      'aria-selected',
      'true',
    );

    await userEvent.setup().click(screen.getByRole('tab', { name: /3 skipped/i }));

    expect(await screen.findByText('No rows with this status.')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Done' })).toHaveAttribute('href', '/admin/imports');
  });
});
