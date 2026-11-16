import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router';
import { ApiError } from '../../shared/api/client';
import { EmptyState, ErrorAlert, Loading } from '../../shared/ui/feedback';
import { Pagination } from '../../shared/ui/Pagination';
import { importsQuery, uploadImport } from './api';

export function ImportsPage() {
  const [page, setPage] = useState(1);
  const [file, setFile] = useState<File | null>(null);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const history = useQuery(importsQuery(page));

  const upload = useMutation({
    mutationFn: uploadImport,
    onSuccess: async (batch) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['imports'] }),
        queryClient.invalidateQueries({ queryKey: ['products'] }),
        queryClient.invalidateQueries({ queryKey: ['categories'] }),
      ]);
      await navigate(`/admin/imports/${batch.id}`);
    },
  });

  const onSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (file) {
      upload.mutate(file);
    }
  };

  const uploadError =
    upload.error instanceof ApiError
      ? (upload.error.firstError('file') ?? upload.error.message)
      : null;

  return (
    <section className="flex flex-col gap-6">
      <form onSubmit={onSubmit} className="card flex flex-col gap-3">
        <h1 className="text-xl font-semibold">Import products from CSV</h1>
        <p className="text-sm text-gray-600">
          Expected columns: name, sku, description, category, price, stock, weight_kg. Valid rows
          are created or updated by SKU. Rows with problems are not imported and are listed with the
          reason so they can be fixed.
        </p>
        <div className="flex flex-wrap items-center gap-3">
          <input
            type="file"
            accept=".csv,text/csv"
            aria-label="CSV file"
            onChange={(event) => setFile(event.target.files?.[0] ?? null)}
            className="text-sm text-gray-600 file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white file:shadow-sm hover:file:bg-indigo-700"
          />
          <button type="submit" className="btn-primary" disabled={!file || upload.isPending}>
            {upload.isPending ? 'Importing…' : 'Import'}
          </button>
        </div>
        {!file && <p className="text-xs text-gray-500">Choose a .csv file to enable the import.</p>}
        {uploadError && <ErrorAlert error={new Error(uploadError)} />}
        {upload.isError && !(upload.error instanceof ApiError) && (
          <ErrorAlert error={upload.error} />
        )}
      </form>

      <section className="flex flex-col gap-3">
        <h2 className="font-semibold">Import history</h2>
        {history.isPending && <Loading />}
        {history.isError && <ErrorAlert error={history.error} />}
        {history.data?.data.length === 0 && <EmptyState>No imports yet.</EmptyState>}
        {history.data && history.data.data.length > 0 && (
          <div className="card overflow-x-auto p-0">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-gray-200 bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                  <th className="px-4 py-2">Date</th>
                  <th className="px-4 py-2">File</th>
                  <th className="px-4 py-2 text-right">Rows</th>
                  <th className="px-4 py-2 text-right">Created</th>
                  <th className="px-4 py-2 text-right">Updated</th>
                  <th className="px-4 py-2 text-right">Rejected</th>
                  <th className="px-4 py-2 text-right">Skipped</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {history.data.data.map((batch) => (
                  <tr key={batch.id}>
                    <td className="px-4 py-2 whitespace-nowrap">
                      {batch.created_at ? new Date(batch.created_at).toLocaleString() : ''}
                    </td>
                    <td className="px-4 py-2">
                      <Link
                        to={`/admin/imports/${batch.id}`}
                        className="text-indigo-700 hover:underline"
                      >
                        {batch.original_filename}
                      </Link>
                    </td>
                    <td className="px-4 py-2 text-right">{batch.total_rows}</td>
                    <td className="px-4 py-2 text-right">{batch.created_count}</td>
                    <td className="px-4 py-2 text-right">{batch.updated_count}</td>
                    <td className="px-4 py-2 text-right text-red-700">{batch.rejected_count}</td>
                    <td className="px-4 py-2 text-right text-gray-500">{batch.skipped_count}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        {history.data && <Pagination meta={history.data.meta} onChange={setPage} />}
      </section>
    </section>
  );
}
