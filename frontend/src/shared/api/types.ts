export type Category = {
  id: number;
  name: string;
};

export type Product = {
  id: number;
  sku: string;
  name: string;
  description: string | null;
  category: Category;
  price: string;
  stock: number;
  weight_kg: string;
  created_at: string | null;
  updated_at: string | null;
};

export type PageMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
};

export type Paginated<T> = {
  data: T[];
  meta: PageMeta;
};

export type Resource<T> = {
  data: T;
};

export type ImportBatch = {
  id: number;
  original_filename: string;
  total_rows: number;
  created_count: number;
  updated_count: number;
  rejected_count: number;
  skipped_count: number;
  created_at: string | null;
};

export type ImportRowStatus = 'created' | 'updated' | 'rejected' | 'skipped';

type ImportIssue = {
  field: string | null;
  severity: 'error' | 'warning';
  message: string;
};

export type ImportRow = {
  id: number;
  line_number: number;
  status: ImportRowStatus;
  raw_data: Record<string, string> | string[];
  issues: ImportIssue[];
  product_id: number | null;
};

type OrderItem = {
  product_id: number;
  sku: string;
  name: string;
  unit_price: string;
  quantity: number;
  line_total: string;
};

export type Order = {
  id: number;
  reference: string;
  status: 'paid';
  customer: { name: string; email: string };
  total: string;
  payment_reference: string;
  items: OrderItem[];
  created_at: string | null;
};
