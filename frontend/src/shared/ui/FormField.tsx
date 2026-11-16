import type { ReactNode } from 'react';

type Props = {
  id: string;
  label: string;
  error?: string | undefined;
  hint?: string;
  children: ReactNode;
};

export function FormField({ id, label, error, hint, children }: Props) {
  return (
    <div className="flex flex-col gap-1">
      <label htmlFor={id} className="text-sm font-medium text-gray-700">
        {label}
      </label>
      {children}
      {hint && !error && <p className="text-xs text-gray-500">{hint}</p>}
      {error && (
        <p id={`${id}-error`} className="text-xs text-red-700">
          {error}
        </p>
      )}
    </div>
  );
}
