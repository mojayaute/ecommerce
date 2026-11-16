import { useQuery } from '@tanstack/react-query';
import { Link, useParams, useSearchParams } from 'react-router';
import type { ImportBatch, ImportRow, ImportRowStatus } from '../../shared/api/types';
import { EmptyState, ErrorAlert, Loading } from '../../shared/ui/feedback';
import { Pagination } from '../../shared/ui/Pagination';
import { importQuery, importRowsQuery } from './api';

const STATUSES: {
  value: ImportRowStatus;
  label: string;
  count: keyof ImportBatch;
  tone: string;
}[] = [
  { value: 'created', label: 'Created', count: 'created_count', tone: 'text-green-700' },
  { value: 'updated', label: 'Updated', count: 'updated_count', tone: 'text-blue-700' },
  { value: 'rejected', label: 'Rejected', count: 'rejected_count', tone: 'text-red-700' },
  { value: 'skipped', label: 'Skipped', count: 'skipped_count', tone: 'text-gray-600' },
];

export function ImportDetailPage() {
  const id = Number(useParams().id);
  const [params, setParams] = useSearchParams();
  const status = (params.get('status') as ImportRowStatus | null) ?? 'rejected';
  const page = Number(params.get('page') ?? 1);

  const batch = useQuery(importQuery(id));
  const rows = useQuery(importRowsQuery(id, status, page));

  if (batch.isPending) {
    return <Loading />;
  }

  if (batch.isError) {
    return <ErrorAlert error={batch.error} />;
  }

  return (
    <section className="flex flex-col gap-4">
      <Link to="/admin/imports" className="text-sm text-indigo-700 hover:underline">
        Back to imports
      </Link>
      <header>
        <h1 className="text-xl font-semibold">{batch.data.original_filename}</h1>
        <p className="text-sm text-gray-500">{batch.data.total_rows} data rows in the file</p>
      </header>

      <div role="tablist" aria-label="Row status" className="grid gap-3 sm:grid-cols-4">
        {STATUSES.map((option) => (
          <button
            key={option.value}
            type="button"
            role="tab"
            aria-selected={status === option.value}
            onClick={() => setParams({ status: option.value })}
            className={`card text-left transition ${status === option.value ? 'ring-2 ring-indigo-500' : 'hover:border-gray-300'}`}
          >
            <span className={`block text-2xl font-semibold ${option.tone}`}>
              {batch.data[option.count]}
            </span>{' '}
            <span className="text-sm text-gray-600">{option.label}</span>
          </button>
        ))}
      </div>

      {rows.isPending && <Loading />}
      {rows.isError && <ErrorAlert error={rows.error} />}
      {rows.data?.data.length === 0 && <EmptyState>No rows with this status.</EmptyState>}

      {rows.data && rows.data.data.length > 0 && (
        <div className="card overflow-x-auto p-0">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-gray-200 bg-gray-50 text-xs text-gray-500 uppercase">
              <tr>
                <th className="px-4 py-2">Line</th>
                <th className="px-4 py-2">SKU</th>
                <th className="px-4 py-2">Name</th>
                <th className="px-4 py-2">Issues</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {rows.data.data.map((row) => (
                <RowLine key={row.id} row={row} />
              ))}
            </tbody>
          </table>
        </div>
      )}

      {rows.data && (
        <Pagination
          meta={rows.data.meta}
          onChange={(next) => setParams({ status, page: String(next) })}
        />
      )}

      <div className="mt-2 flex justify-end border-t border-gray-200 pt-6">
        <Link to="/admin/imports" className="btn-primary px-10 py-3 text-base shadow-md">
          Done
        </Link>
      </div>
    </section>
  );
}

function RowLine({ row }: { row: ImportRow }) {
  const raw: Record<string, string> = Array.isArray(row.raw_data) ? {} : row.raw_data;

  return (
    <tr className="align-top">
      <td className="px-4 py-2 text-gray-500">{row.line_number}</td>
      <td className="px-4 py-2 font-mono text-xs">{raw.sku || '(empty)'}</td>
      <td className="px-4 py-2">
        {raw.name?.trim() || <span className="text-gray-400">(empty)</span>}
      </td>
      <td className="px-4 py-2">
        {row.issues.length === 0 ? (
          <span className="text-gray-400">None</span>
        ) : (
          <ul className="flex flex-col gap-1">
            {row.issues.map((issue, index) => (
              <li
                key={index}
                className={issue.severity === 'error' ? 'text-red-700' : 'text-amber-700'}
              >
                {issue.field && <strong className="font-medium">{issue.field}: </strong>}
                {issue.message}
              </li>
            ))}
          </ul>
        )}
      </td>
    </tr>
  );
}
