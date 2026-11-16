import { Link } from 'react-router';
import { useToastStore } from './toast';

export function Toaster() {
  const toasts = useToastStore((state) => state.toasts);
  const dismiss = useToastStore((state) => state.dismiss);

  return (
    <div
      aria-live="polite"
      className="pointer-events-none fixed top-16 right-4 z-50 flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2"
    >
      {toasts.map((toast) => (
        <div
          key={toast.id}
          className="pointer-events-auto flex items-center gap-3 rounded-lg border border-green-200 border-l-4 border-l-green-500 bg-green-50 px-4 py-3 text-sm text-gray-800 shadow-lg"
        >
          <span className="flex-1">{toast.message}</span>
          {toast.action && (
            <Link
              to={toast.action.to}
              onClick={() => dismiss(toast.id)}
              className="font-medium whitespace-nowrap text-indigo-700 hover:text-indigo-900 hover:underline"
            >
              {toast.action.label}
            </Link>
          )}
          <button
            type="button"
            aria-label="Dismiss notification"
            onClick={() => dismiss(toast.id)}
            className="text-gray-400 hover:text-gray-700"
          >
            ×
          </button>
        </div>
      ))}
    </div>
  );
}
