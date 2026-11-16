import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { Product } from '../../shared/api/types';
import { fromCents, toCents } from '../../shared/money';

export type CartItem = {
  productId: number;
  sku: string;
  name: string;
  price: string;
  stock: number;
  quantity: number;
};

type CartState = {
  items: CartItem[];
  add: (product: Product, quantity?: number) => void;
  setQuantity: (productId: number, quantity: number) => void;
  remove: (productId: number) => void;
  refresh: (products: Product[], unavailableIds: number[]) => void;
  clear: () => void;
};

const clamp = (quantity: number, stock: number) => Math.max(0, Math.min(quantity, stock));

const fromProduct = (product: Product, quantity: number): CartItem => ({
  productId: product.id,
  sku: product.sku,
  name: product.name,
  price: product.price,
  stock: product.stock,
  quantity: clamp(quantity, product.stock),
});

export const useCartStore = create<CartState>()(
  persist(
    (set) => ({
      items: [],
      add: (product, quantity = 1) =>
        set(({ items }) => {
          const existing = items.find((item) => item.productId === product.id);
          const next = fromProduct(product, (existing?.quantity ?? 0) + quantity);

          if (next.quantity === 0) {
            return { items };
          }

          return existing
            ? { items: items.map((item) => (item.productId === product.id ? next : item)) }
            : { items: [...items, next] };
        }),
      setQuantity: (productId, quantity) =>
        set(({ items }) => ({
          items: items
            .map((item) =>
              item.productId === productId
                ? { ...item, quantity: clamp(quantity, item.stock) }
                : item,
            )
            .filter((item) => item.quantity > 0),
        })),
      remove: (productId) =>
        set(({ items }) => ({ items: items.filter((item) => item.productId !== productId) })),
      refresh: (products, unavailableIds) =>
        set(({ items }) => ({
          items: items
            .filter((item) => !unavailableIds.includes(item.productId))
            .map((item) => {
              const product = products.find((candidate) => candidate.id === item.productId);

              return product ? fromProduct(product, item.quantity) : item;
            })
            .filter((item) => item.quantity > 0),
        })),
      clear: () => set({ items: [] }),
    }),
    { name: 'mvp2-cart' },
  ),
);

export function lineTotal(item: CartItem): string {
  return fromCents(toCents(item.price) * item.quantity);
}

export function cartTotal(items: CartItem[]): string {
  return fromCents(items.reduce((sum, item) => sum + toCents(item.price) * item.quantity, 0));
}

export function cartCount(items: CartItem[]): number {
  return items.reduce((sum, item) => sum + item.quantity, 0);
}
