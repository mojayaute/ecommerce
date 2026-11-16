type ErrorBody = {
  message?: unknown;
  code?: unknown;
  details?: unknown;
  errors?: unknown;
};

export class ApiError extends Error {
  readonly status: number;
  readonly code: string | null;
  readonly details: Record<string, unknown>;
  readonly fieldErrors: Record<string, string[]>;

  constructor(status: number, body: unknown) {
    const error: ErrorBody = typeof body === 'object' && body !== null ? body : {};

    super(
      typeof error.message === 'string' ? error.message : 'Something went wrong. Please try again.',
    );

    this.name = 'ApiError';
    this.status = status;
    this.code = typeof error.code === 'string' ? error.code : null;
    this.details = isRecord(error.details) ? error.details : {};
    this.fieldErrors = isRecord(error.errors) ? toFieldErrors(error.errors) : {};
  }

  firstError(field: string): string | undefined {
    return this.fieldErrors[field]?.[0];
  }
}

type QueryValue = string | number | boolean | null | undefined;

export function withQuery(path: string, params: Record<string, QueryValue>): string {
  const query = new URLSearchParams();

  for (const [key, value] of Object.entries(params)) {
    if (value !== null && value !== undefined && value !== '' && value !== false) {
      query.set(key, String(value));
    }
  }

  const search = query.toString();

  return search === '' ? path : `${path}?${search}`;
}

export async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers);
  headers.set('Accept', 'application/json');

  if (typeof init.body === 'string') {
    headers.set('Content-Type', 'application/json');
  }

  const response = await fetch(`/api/v1${path}`, { ...init, headers });

  if (response.status === 204) {
    return undefined as T;
  }

  const body: unknown = await response.json().catch(() => null);

  if (!response.ok) {
    throw new ApiError(response.status, body);
  }

  return body as T;
}

export const api = {
  get: <T>(path: string) => request<T>(path),
  post: <T>(path: string, body: unknown, headers?: HeadersInit) =>
    request<T>(path, {
      method: 'POST',
      body: body instanceof FormData ? body : JSON.stringify(body),
      headers,
    }),
  put: <T>(path: string, body: unknown) =>
    request<T>(path, { method: 'PUT', body: JSON.stringify(body) }),
  delete: (path: string) => request<void>(path, { method: 'DELETE' }),
};

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function toFieldErrors(errors: Record<string, unknown>): Record<string, string[]> {
  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => [
      field,
      Array.isArray(messages) ? messages.filter((m): m is string => typeof m === 'string') : [],
    ]),
  );
}
