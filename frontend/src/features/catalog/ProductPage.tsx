import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';
import { Link, useParams } from 'react-router';
import { formatMoney } from '../../shared/money';
import { ErrorAlert, Loading } from '../../shared/ui/feedback';
import { useCartStore } from '../cart/cartStore';
import { useAddToCart } from '../cart/useAddToCart';
import { productQuery } from './api';
import { StockBadge } from './StockBadge';

export function ProductPage() {
  const id = Number(useParams().id);
  const product = useQuery(productQuery(id));
  const addToCart = useAddToCart();
  const inCart = useCartStore(
    (state) => state.items.find((item) => item.productId === id)?.quantity ?? 0,
  );
  const [quantity, setQuantity] = useState(1);

  if (product.isPending) {
    return <Loading />;
  }

  if (product.isError) {
    return <ErrorAlert error={product.error} />;
  }

  const available = product.data.stock - inCart;

  return (
    <article className="card mx-auto flex max-w-2xl flex-col gap-4">
      <Link to="/" className="text-sm text-indigo-700 hover:underline">
        Back to catalog
      </Link>
      <div>
        <p className="text-xs tracking-wide text-gray-500 uppercase">
          {product.data.category.name}
        </p>
        <h1 className="text-2xl font-semibold text-gray-900">{product.data.name}</h1>
        <p className="text-sm text-gray-400">SKU {product.data.sku}</p>
      </div>
      {product.data.description && <p className="text-gray-700">{product.data.description}</p>}
      <dl className="grid grid-cols-2 gap-2 text-sm">
        <dt className="text-gray-500">Price</dt>
        <dd className="text-lg font-semibold">{formatMoney(product.data.price)}</dd>
        <dt className="text-gray-500">Weight</dt>
        <dd>{product.data.weight_kg} kg</dd>
        <dt className="text-gray-500">Availability</dt>
        <dd>
          <StockBadge stock={product.data.stock} />
        </dd>
      </dl>
      <div className="flex items-end gap-3">
        <label className="flex flex-col gap-1 text-sm">
          Quantity
          <input
            type="number"
            min={1}
            max={Math.max(available, 1)}
            value={quantity}
            onChange={(event) => setQuantity(Math.max(1, Number(event.target.value)))}
            className="input w-24"
          />
        </label>
        <button
          type="button"
          className="btn-primary"
          disabled={available <= 0}
          onClick={() => addToCart(product.data, Math.min(quantity, available))}
        >
          Add to cart
        </button>
      </div>
    </article>
  );
}
