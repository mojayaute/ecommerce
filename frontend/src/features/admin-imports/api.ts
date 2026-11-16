import { keepPreviousData, queryOptions } from '@tanstack/react-query';
import { api, withQuery } from '../../shared/api/client';
import type {
  ImportBatch,
  ImportRow,
  ImportRowStatus,
  Paginated,
  Resource,
} from '../../shared/api/types';

export const importsQuery = (page: number) =>
  queryOptions({
    queryKey: ['imports', 'list', page],
    queryFn: () => api.get<Paginated<ImportBatch>>(withQuery('/admin/imports', { page })),
    placeholderData: keepPreviousData,
  });

export const importQuery = (id: number) =>
  queryOptions({
    queryKey: ['imports', 'detail', id],
    queryFn: async () => (await api.get<Resource<ImportBatch>>(`/admin/imports/${id}`)).data,
  });

export const importRowsQuery = (id: number, status: ImportRowStatus | '', page: number) =>
  queryOptions({
    queryKey: ['imports', 'rows', id, { status, page }],
    queryFn: () =>
      api.get<Paginated<ImportRow>>(withQuery(`/admin/imports/${id}/rows`, { status, page })),
    placeholderData: keepPreviousData,
  });

export const uploadImport = async (file: File) => {
  const body = new FormData();
  body.append('file', file);

  return (await api.post<Resource<ImportBatch>>('/admin/imports', body)).data;
};
