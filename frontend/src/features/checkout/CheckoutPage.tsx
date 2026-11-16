import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { Link, useNavigate } from 'react-router';
import { z } from 'zod';
import { api, ApiError } from '../../shared/api/client';
import type { Order, Product, Resource } from '../../shared/api/types';
import { formatMoney } from '../../shared/money';
import { EmptyState, ErrorAlert, Notice } from '../../shared/ui/feedback';
import { FormField } from '../../shared/ui/FormField';
import { uuid } from '../../shared/uuid';
import { cartTotal, lineTotal, useCartStore, type CartItem } from '../cart/cartStore';
import { productQuery } from '../catalog/api';

const schema = z.object({
  name: z.string().trim().min(1, 'Enter your name.').max(255, 'The name is too long.'),
  email: z.email('Enter a valid email address.'),
});

type CustomerValues = z.infer<typeof schema>;

const CART_UPDATES: Record<string, string> = {
  price_changed:
    'Prices changed since you added these items. Your cart was updated: review the new total and pay again.',
  insufficient_stock:
    'Some items no longer have enough stock. Your cart was updated to what is available.',
  product_unavailable: 'Some products are no longer sold and were removed from your cart.',
};

export function CheckoutPage() {
  const items = useCartStore((state) => state.items);
  const clear = useCartStore((state) => state.clear);
  const refresh = useCartStore((state) => state.refresh);
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const [idempotencyKey] = useState(uuid);
  const [notice, setNotice] = useState<string | null>(null);

  const form = useForm<CustomerValues>({
    resolver: zodResolver(schema),
    defaultValues: { name: '', email: '' },
  });

  const refreshCart = async (cart: CartItem[]) => {
    const results = await Promise.allSettled(
      cart.map((item) => queryClient.fetchQuery({ ...productQuery(item.productId), staleTime: 0 })),
    );

    const products: Product[] = [];
    const unavailable: number[] = [];

    results.forEach((result, index) => {
      const productId = cart[index]?.productId;

      if (result.status === 'fulfilled') {
        products.push(result.value);
      } else if (
        productId !== undefined &&
        result.reason instanceof ApiError &&
        result.reason.status === 404
      ) {
        unavailable.push(productId);
      }
    });

    refresh(products, unavailable);
  };

  const placeOrder = useMutation({
    mutationFn: (customer: CustomerValues) =>
      api.post<Resource<Order>>(
        '/orders',
        {
          customer,
          items: items.map((item) => ({ product_id: item.productId, quantity: item.quantity })),
          expected_total: cartTotal(items),
        },
        { 'Idempotency-Key': idempotencyKey },
      ),
    onMutate: () => setNotice(null),
    onSuccess: async ({ data: order }) => {
      clear();
      await queryClient.invalidateQueries({ queryKey: ['products'] });
      await navigate(`/orders/${order.reference}`, { state: { order }, replace: true });
    },
    onError: async (error) => {
      if (!(error instanceof ApiError)) {
        return;
      }

      for (const field of ['name', 'email'] as const) {
        const message = error.firstError(`customer.${field}`);

        if (message) {
          form.setError(field, { message });
        }
      }

      const update = error.code ? CART_UPDATES[error.code] : undefined;

      if (update) {
        await refreshCart(items);
        setNotice(update);
      }
    },
  });

  if (items.length === 0) {
    return (
      <EmptyState>
        There is nothing to pay for yet.{' '}
        <Link to="/" className="text-indigo-700 hover:underline">
          Browse the catalog
        </Link>
      </EmptyState>
    );
  }

  const showError =
    placeOrder.isError &&
    notice === null &&
    !(placeOrder.error instanceof ApiError && Object.keys(placeOrder.error.fieldErrors).length > 0);

  return (
    <div className="grid gap-6 md:grid-cols-[3fr_2fr]">
      <form
        noValidate
        onSubmit={form.handleSubmit((values) => placeOrder.mutate(values))}
        className="card flex flex-col gap-4"
      >
        <h1 className="text-xl font-semibold">Checkout</h1>
        {notice && <Notice>{notice}</Notice>}
        {showError && <ErrorAlert error={placeOrder.error} />}
        <FormField id="name" label="Full name" error={form.formState.errors.name?.message}>
          <input
            id="name"
            autoComplete="name"
            aria-invalid={form.formState.errors.name ? true : undefined}
            className="input"
            {...form.register('name')}
          />
        </FormField>
        <FormField
          id="email"
          label="Email"
          error={form.formState.errors.email?.message}
          hint="Payments are simulated. Use decline@example.com to see a declined payment."
        >
          <input
            id="email"
            type="email"
            autoComplete="email"
            aria-invalid={form.formState.errors.email ? true : undefined}
            className="input"
            {...form.register('email')}
          />
        </FormField>
        <button type="submit" className="btn-primary" disabled={placeOrder.isPending}>
          {placeOrder.isPending ? 'Processing payment…' : `Pay ${formatMoney(cartTotal(items))}`}
        </button>
      </form>

      <aside className="card flex flex-col gap-3 self-start">
        <h2 className="font-semibold">Order summary</h2>
        <ul className="divide-y divide-gray-100 text-sm">
          {items.map((item) => (
            <li key={item.productId} className="flex justify-between py-2">
              <span>
                {item.quantity} × {item.name}
              </span>
              <span>{formatMoney(lineTotal(item))}</span>
            </li>
          ))}
        </ul>
        <p className="flex justify-between border-t border-gray-200 pt-3 font-semibold">
          <span>Total</span>
          <span>{formatMoney(cartTotal(items))}</span>
        </p>
      </aside>
    </div>
  );
}
