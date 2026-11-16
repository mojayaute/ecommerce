import { beforeEach, describe, expect, it } from 'vitest';
import { aProduct } from '../../test/fixtures';
import { cartCount, cartTotal, useCartStore } from './cartStore';

const cart = () => useCartStore.getState();

describe('cart store', () => {
  beforeEach(() => useCartStore.setState({ items: [] }));

  it('merges repeated products and never exceeds the stock', () => {
    const mouse = aProduct({ stock: 3 });

    cart().add(mouse);
    cart().add(mouse, 5);

    expect(cart().items).toHaveLength(1);
    expect(cart().items[0]?.quantity).toBe(3);
  });

  it('does not add a product that is out of stock', () => {
    cart().add(aProduct({ stock: 0 }));

    expect(cart().items).toEqual([]);
  });

  it('removes an item when its quantity is set to zero', () => {
    cart().add(aProduct());
    cart().setQuantity(3, 0);

    expect(cart().items).toEqual([]);
  });

  it('refreshes prices and stock from the server and drops unavailable products', () => {
    cart().add(aProduct({ id: 1, price: '10.00' }), 4);
    cart().add(aProduct({ id: 2 }));

    cart().refresh([aProduct({ id: 1, price: '12.50', stock: 2 })], [2]);

    expect(cart().items).toEqual([
      expect.objectContaining({ productId: 1, price: '12.50', stock: 2, quantity: 2 }),
    ]);
  });

  it('totals the cart in exact cents', () => {
    cart().add(aProduct({ id: 1, price: '0.10' }));
    cart().add(aProduct({ id: 2, price: '0.20' }));
    cart().add(aProduct({ id: 3, price: '19.99' }), 3);

    expect(cartTotal(cart().items)).toBe('60.27');
    expect(cartCount(cart().items)).toBe(5);
  });
});
