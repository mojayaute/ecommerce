import type { RouteObject } from 'react-router';
import { CartPage } from '../features/cart/CartPage';
import { CatalogPage } from '../features/catalog/CatalogPage';
import { ProductPage } from '../features/catalog/ProductPage';
import { CheckoutPage } from '../features/checkout/CheckoutPage';
import { OrderConfirmationPage } from '../features/checkout/OrderConfirmationPage';
import { Layout } from './Layout';
import { NotFoundPage } from './NotFoundPage';

const adminProducts = () => import('../features/admin-products/AdminProductsPage');
const productForm = () => import('../features/admin-products/ProductFormPage');
const imports = () => import('../features/admin-imports/ImportsPage');
const importDetail = () => import('../features/admin-imports/ImportDetailPage');

export const routes: RouteObject[] = [
  {
    element: <Layout />,
    children: [
      { index: true, element: <CatalogPage /> },
      { path: 'products/:id', element: <ProductPage /> },
      { path: 'cart', element: <CartPage /> },
      { path: 'checkout', element: <CheckoutPage /> },
      { path: 'orders/:reference', element: <OrderConfirmationPage /> },
      {
        path: 'admin/products',
        lazy: async () => ({ Component: (await adminProducts()).AdminProductsPage }),
      },
      {
        path: 'admin/products/new',
        lazy: async () => ({ Component: (await productForm()).ProductFormPage }),
      },
      {
        path: 'admin/products/:id/edit',
        lazy: async () => ({ Component: (await productForm()).ProductFormPage }),
      },
      { path: 'admin/imports', lazy: async () => ({ Component: (await imports()).ImportsPage }) },
      {
        path: 'admin/imports/:id',
        lazy: async () => ({ Component: (await importDetail()).ImportDetailPage }),
      },
      { path: '*', element: <NotFoundPage /> },
    ],
  },
];
