import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it } from 'vitest';
import { aProduct } from '../../test/fixtures';
import { mockApi, renderRoute } from '../../test/utils';
import { useCartStore } from '../cart/cartStore';
import { useToastStore } from '../../shared/ui/toast';

const page = { current_page: 1, last_page: 1, per_page: 20, total: 1, from: 1, to: 1 };

describe('catalog', () => {
  beforeEach(() => {
    useCartStore.setState({ items: [] });
    useToastStore.setState({ toasts: [] });
  });

  it('confirms with a notification when a product is added to the cart', async () => {
    mockApi({
      'GET /api/v1/products?page=1': { body: { data: [aProduct()], meta: page } },
      'GET /api/v1/categories': { body: { data: [] } },
    });
    const user = userEvent.setup();

    renderRoute('/');
    await user.click(await screen.findByRole('button', { name: 'Add to cart' }));

    expect(await screen.findByText('Added Wireless Mouse to the cart')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'View cart' })).toHaveAttribute('href', '/cart');
    expect(screen.getByRole('link', { name: /^Cart\s*1$/ })).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: 'Dismiss notification' }));

    expect(screen.queryByText('Added Wireless Mouse to the cart')).not.toBeInTheDocument();
  });
});
