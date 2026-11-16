import { Link, useLocation, useParams } from 'react-router';
import type { Order } from '../../shared/api/types';
import { formatMoney } from '../../shared/money';

export function OrderConfirmationPage() {
  const { reference } = useParams();
  const order = (useLocation().state as { order?: Order } | null)?.order;

  return (
    <section className="card mx-auto flex max-w-xl flex-col gap-4">
      <h1 className="text-xl font-semibold text-green-800">Thank you, your order is confirmed</h1>
      <p className="text-sm text-gray-600">
        Order reference <strong className="text-gray-900">{reference}</strong>
      </p>

      {order && (
        <>
          <p className="text-sm text-gray-600">
            A receipt would be sent to <strong>{order.customer.email}</strong>. Payment reference{' '}
            <code className="text-xs">{order.payment_reference}</code>.
          </p>
          <ul className="divide-y divide-gray-100 text-sm">
            {order.items.map((item) => (
              <li key={item.product_id} className="flex justify-between py-2">
                <span>
                  {item.quantity} × {item.name}{' '}
                  <span className="text-xs text-gray-400">{item.sku}</span>
                </span>
                <span>{formatMoney(item.line_total)}</span>
              </li>
            ))}
          </ul>
          <p className="flex justify-between border-t border-gray-200 pt-3 font-semibold">
            <span>Total paid</span>
            <span>{formatMoney(order.total)}</span>
          </p>
        </>
      )}

      <Link to="/" className="btn-primary self-start">
        Continue shopping
      </Link>
    </section>
  );
}
