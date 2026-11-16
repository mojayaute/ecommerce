import type { Product } from '../../shared/api/types';
import { toast } from '../../shared/ui/toast';
import { useCartStore } from './cartStore';

export function useAddToCart() {
  const add = useCartStore((state) => state.add);

  return (product: Product, quantity = 1) => {
    add(product, quantity);

    const what = quantity > 1 ? `${quantity} × ${product.name}` : product.name;
    toast(`Added ${what} to the cart`, { label: 'View cart', to: '/cart' });
  };
}
