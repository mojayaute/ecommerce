import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import { Link, useNavigate, useParams } from 'react-router';
import { ApiError } from '../../shared/api/client';
import { ErrorAlert, Loading } from '../../shared/ui/feedback';
import { FormField } from '../../shared/ui/FormField';
import { categoriesQuery } from '../catalog/api';
import { adminProductQuery, saveProduct } from './api';
import {
  emptyProduct,
  PRODUCT_FIELDS,
  productSchema,
  toFormValues,
  toPayload,
  type ProductFormValues,
} from './productSchema';

export function ProductFormPage() {
  const { id } = useParams();
  const productId = id === undefined ? null : Number(id);
  const product = useQuery({ ...adminProductQuery(productId ?? 0), enabled: productId !== null });

  if (productId !== null && product.isPending) {
    return <Loading />;
  }

  if (productId !== null && product.isError) {
    return <ErrorAlert error={product.error} />;
  }

  return (
    <ProductForm
      key={productId ?? 'new'}
      productId={productId}
      initialValues={product.data ? toFormValues(product.data) : emptyProduct}
    />
  );
}

type FormProps = {
  productId: number | null;
  initialValues: ProductFormValues;
};

const LABELS: Record<keyof ProductFormValues, string> = {
  sku: 'SKU',
  name: 'Name',
  description: 'Description',
  category: 'Category',
  price: 'Price (USD)',
  stock: 'Stock',
  weight_kg: 'Weight (kg)',
};

function ProductForm({ productId, initialValues }: FormProps) {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const categories = useQuery(categoriesQuery());

  const form = useForm<ProductFormValues>({
    resolver: zodResolver(productSchema),
    defaultValues: initialValues,
  });
  const errors = form.formState.errors;

  const save = useMutation({
    mutationFn: (values: ProductFormValues) => saveProduct(productId, toPayload(values)),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['products'] }),
        queryClient.invalidateQueries({ queryKey: ['categories'] }),
      ]);
      await navigate('/admin/products');
    },
    onError: (error) => {
      if (error instanceof ApiError) {
        for (const field of PRODUCT_FIELDS) {
          const message = error.firstError(field);

          if (message) {
            form.setError(field, { message });
          }
        }
      }
    },
  });

  const hasFieldErrors =
    save.error instanceof ApiError && Object.keys(save.error.fieldErrors).length > 0;

  const input = (field: Exclude<keyof ProductFormValues, 'description'>, extra = {}) => (
    <FormField id={field} label={LABELS[field]} error={errors[field]?.message}>
      <input
        id={field}
        aria-invalid={errors[field] ? true : undefined}
        className="input"
        {...extra}
        {...form.register(field)}
      />
    </FormField>
  );

  return (
    <form
      noValidate
      onSubmit={form.handleSubmit((values) => save.mutate(values))}
      className="card mx-auto flex max-w-2xl flex-col gap-4"
    >
      <h1 className="text-xl font-semibold">
        {productId === null ? 'New product' : 'Edit product'}
      </h1>

      {save.isError && !hasFieldErrors && <ErrorAlert error={save.error} />}

      <div className="grid gap-4 sm:grid-cols-2">
        {input('sku', { autoCapitalize: 'characters' })}
        {input('name')}
      </div>

      <FormField id="description" label={LABELS.description} error={errors.description?.message}>
        <textarea
          id="description"
          rows={3}
          aria-invalid={errors.description ? true : undefined}
          className="input"
          {...form.register('description')}
        />
      </FormField>

      {input('category', { list: 'category-options' })}
      <datalist id="category-options">
        {categories.data?.map((category) => (
          <option key={category.id} value={category.name} />
        ))}
      </datalist>

      <div className="grid gap-4 sm:grid-cols-3">
        {input('price', { inputMode: 'decimal' })}
        {input('stock', { inputMode: 'numeric' })}
        {input('weight_kg', { inputMode: 'decimal' })}
      </div>

      <div className="flex justify-end gap-2">
        <Link to="/admin/products" className="btn-secondary">
          Cancel
        </Link>
        <button type="submit" className="btn-primary" disabled={save.isPending}>
          {save.isPending ? 'Saving…' : 'Save product'}
        </button>
      </div>
    </form>
  );
}
