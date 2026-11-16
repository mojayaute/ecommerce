import { Link } from 'react-router';
import { formatMoney } from '../../shared/money';
import { EmptyState } from '../../shared/ui/feedback';
import { cartTotal, useCartStore } from './cartStore';

export function CartPage() {
  const items = useCartStore((state) => state.items);
  const setQuantity = useCartStore((state) => state.setQuantity);
  const remove = useCartStore((state) => state.remove);

  if (items.length === 0) {
    return (
      <EmptyState>
        Your cart is empty.{' '}
        <Link to="/" className="text-indigo-700 hover:underline">
          Browse the catalog
        </Link>
      </EmptyState>
    );
  }

  return (
    <section className="card flex flex-col gap-4">
      <h1 className="text-xl font-semibold">Cart</h1>
      <ul className="divide-y divide-gray-100">
        {items.map((item) => (
          <li key={item.productId} className="flex flex-wrap items-center gap-4 py-3">
            <div className="flex-1">
              <Link to={`/products/${item.productId}`} className="font-medium hover:underline">
                {item.name}
              </Link>
              <p className="text-xs text-gray-400">{item.sku}</p>
            </div>
            <span className="w-24 text-right text-sm">{formatMoney(item.price)}</span>
            <input
              type="number"
              min={1}
              max={item.stock}
              value={item.quantity}
              aria-label={`Quantity of ${item.name}`}
              onChange={(event) => setQuantity(item.productId, Number(event.target.value))}
              className="input w-20"
            />
            <button type="button" className="btn-danger" onClick={() => remove(item.productId)}>
              Remove
            </button>
          </li>
        ))}
      </ul>
      <div className="flex items-center justify-between border-t border-gray-200 pt-4">
        <span className="text-lg">
          Total <strong>{formatMoney(cartTotal(items))}</strong>
        </span>
        <Link to="/checkout" className="btn-primary">
          Checkout
        </Link>
      </div>
    </section>
  );
}
