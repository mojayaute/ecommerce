import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render } from '@testing-library/react';
import { createMemoryRouter, RouterProvider } from 'react-router';
import { vi } from 'vitest';
import { routes } from '../app/routes';

type MockResponse = { status?: number; body?: unknown };
type Handler = (init: RequestInit) => MockResponse;

export function mockApi(handlers: Record<string, Handler | MockResponse>) {
  const fetchMock = vi.fn(async (input: RequestInfo | URL, init: RequestInit = {}) => {
    const url = new URL(String(input), 'http://localhost');
    const key = `${init.method ?? 'GET'} ${url.pathname}${url.search}`;
    const handler = handlers[key] ?? handlers[`${init.method ?? 'GET'} ${url.pathname}`];

    if (handler === undefined) {
      throw new Error(`Unexpected request: ${key}`);
    }

    const { status = 200, body = null } = typeof handler === 'function' ? handler(init) : handler;

    return new Response(status === 204 ? null : JSON.stringify(body), {
      status,
      headers: { 'Content-Type': 'application/json' },
    });
  });

  vi.stubGlobal('fetch', fetchMock);

  return fetchMock;
}

export function renderRoute(path: string) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  const router = createMemoryRouter(routes, { initialEntries: [path] });

  render(
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
    </QueryClientProvider>,
  );

  return { router };
}
