import type { ReactNode } from 'react';

export function Loading({ label = 'Loading…' }: { label?: string }) {
  return (
    <p role="status" className="py-8 text-center text-sm text-gray-500">
      {label}
    </p>
  );
}

export function ErrorAlert({ error }: { error: unknown }) {
  const message =
    error instanceof Error ? error.message : 'Something went wrong. Please try again.';

  return (
    <div
      role="alert"
      className="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800"
    >
      {message}
    </div>
  );
}

export function Notice({ children }: { children: ReactNode }) {
  return (
    <div
      role="status"
      className="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"
    >
      {children}
    </div>
  );
}

export function EmptyState({ children }: { children: ReactNode }) {
  return (
    <div className="rounded-lg border border-dashed border-gray-300 p-10 text-center text-gray-500">
      {children}
    </div>
  );
}
