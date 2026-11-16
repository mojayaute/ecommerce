import type { PageMeta } from '../api/types';

type Props = {
  meta: PageMeta;
  onChange: (page: number) => void;
};

export function Pagination({ meta, onChange }: Props) {
  if (meta.last_page <= 1) {
    return null;
  }

  return (
    <nav aria-label="Pagination" className="mt-6 flex items-center justify-between text-sm">
      <span className="text-gray-500">
        {meta.from}-{meta.to} of {meta.total}
      </span>
      <div className="flex gap-2">
        <button
          type="button"
          className="btn-secondary"
          disabled={meta.current_page <= 1}
          onClick={() => onChange(meta.current_page - 1)}
        >
          Previous
        </button>
        <span className="self-center text-gray-600">
          Page {meta.current_page} of {meta.last_page}
        </span>
        <button
          type="button"
          className="btn-secondary"
          disabled={meta.current_page >= meta.last_page}
          onClick={() => onChange(meta.current_page + 1)}
        >
          Next
        </button>
      </div>
    </nav>
  );
}
