import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it } from 'vitest';
import { aProduct } from '../../test/fixtures';
import { mockApi, renderRoute } from '../../test/utils';
import { useCartStore } from '../cart/cartStore';

const order = {
  id: 1,
  reference: 'ORD-20261116-ABC123',
  status: 'paid',
  customer: { name: 'Ada Lovelace', email: 'ada@example.com' },
  total: '59.98',
  payment_reference: 'fake_123',
  items: [
    {
      product_id: 3,
      sku: 'WM-042',
      name: 'Wireless Mouse',
      unit_price: '29.99',
      quantity: 2,
      line_total: '59.98',
    },
  ],
  created_at: null,
};

async function fillAndPay() {
  const user = userEvent.setup();
  await user.type(await screen.findByLabelText('Full name'), 'Ada Lovelace');
  await user.type(screen.getByLabelText('Email'), 'ada@example.com');
  await user.click(screen.getByRole('button', { name: /pay/i }));
}

describe('checkout', () => {
  beforeEach(() => {
    useCartStore.setState({ items: [] });
    useCartStore.getState().add(aProduct(), 2);
  });

  it('pays with an idempotency key and the total the customer saw, then shows the order', async () => {
    const fetchMock = mockApi({
      'POST /api/v1/orders': { status: 201, body: { data: order } },
      'GET /api/v1/products': { body: { data: [], meta: {} } },
    });

    const { router } = renderRoute('/checkout');
    await fillAndPay();

    expect(await screen.findByText('ORD-20261116-ABC123')).toBeInTheDocument();
    expect(router.state.location.pathname).toBe('/orders/ORD-20261116-ABC123');
    expect(useCartStore.getState().items).toEqual([]);

    const [, init] = fetchMock.mock.calls[0] ?? [];
    expect(new Headers(init?.headers).get('Idempotency-Key')).toMatch(/^[0-9a-f-]{36}$/);
    expect(JSON.parse(String(init?.body))).toEqual({
      customer: { name: 'Ada Lovelace', email: 'ada@example.com' },
      items: [{ product_id: 3, quantity: 2 }],
      expected_total: '59.98',
    });
  });

  it('refreshes the cart and asks to review the new total when prices changed', async () => {
    mockApi({
      'POST /api/v1/orders': {
        status: 409,
        body: { message: 'The order total is now 62.00.', code: 'price_changed' },
      },
      'GET /api/v1/products/3': { body: { data: aProduct({ price: '31.00' }) } },
    });

    renderRoute('/checkout');
    await fillAndPay();

    expect(await screen.findByText(/prices changed/i)).toBeInTheDocument();
    expect(await screen.findByRole('button', { name: 'Pay $62.00' })).toBeInTheDocument();
  });

  it('shows the reason when the payment is declined and keeps the cart', async () => {
    mockApi({
      'POST /api/v1/orders': {
        status: 402,
        body: {
          message: 'The payment was declined. No charge was made.',
          code: 'payment_declined',
        },
      },
    });

    renderRoute('/checkout');
    await fillAndPay();

    expect(await screen.findByRole('alert')).toHaveTextContent('The payment was declined.');
    expect(useCartStore.getState().items).toHaveLength(1);
  });
});
