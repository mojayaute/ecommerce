import { NavLink, Outlet } from 'react-router';
import { cartCount, useCartStore } from '../features/cart/cartStore';
import { Toaster } from '../shared/ui/Toaster';

const linkClass = ({ isActive }: { isActive: boolean }) =>
  `rounded-md px-3 py-1.5 text-sm font-medium ${isActive ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900'}`;

export function Layout() {
  const count = useCartStore((state) => cartCount(state.items));

  return (
    <div className="min-h-screen">
      <header className="border-b border-gray-200 bg-white">
        <div className="mx-auto flex max-w-6xl flex-wrap items-center gap-2 px-4 py-3">
          <NavLink to="/" className="mr-4 text-lg font-semibold text-gray-900">
            MVP2 Commerce
          </NavLink>
          <nav aria-label="Store" className="flex gap-1">
            <NavLink to="/" end className={linkClass}>
              Catalog
            </NavLink>
            <NavLink to="/cart" className={linkClass}>
              Cart
              {count > 0 && (
                <span className="ml-1 rounded-full bg-indigo-600 px-2 text-xs text-white">
                  {count}
                </span>
              )}
            </NavLink>
          </nav>
          <nav aria-label="Admin" className="ml-auto flex items-center gap-1">
            <span className="text-xs tracking-wide text-gray-400 uppercase">Admin</span>
            <NavLink to="/admin/products" className={linkClass}>
              Products
            </NavLink>
            <NavLink to="/admin/imports" className={linkClass}>
              Imports
            </NavLink>
          </nav>
        </div>
      </header>
      <main className="mx-auto max-w-6xl px-4 py-6">
        <Outlet />
      </main>
      <Toaster />
    </div>
  );
}
