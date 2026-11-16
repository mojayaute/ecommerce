import { z } from 'zod';
import type { Product } from '../../shared/api/types';
import { toCents } from '../../shared/money';
import type { ProductPayload } from './api';

export const productSchema = z.object({
  sku: z
    .string()
    .trim()
    .min(1, 'Enter a SKU.')
    .max(50, 'Use at most 50 characters.')
    .regex(/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/, 'Use letters, numbers and single dashes only.'),
  name: z.string().trim().min(1, 'Enter a name.').max(255, 'Use at most 255 characters.'),
  description: z.string().trim().max(2000, 'Use at most 2000 characters.'),
  category: z.string().trim().min(1, 'Enter a category.').max(100, 'Use at most 100 characters.'),
  price: z
    .string()
    .trim()
    .regex(/^\d+(?:\.\d{1,2})?$/, 'Enter a price like 19.99.')
    .refine((value) => toCents(value) > 0, 'The price must be greater than 0.'),
  stock: z.string().trim().regex(/^\d+$/, 'Enter a whole number, 0 or more.'),
  weight_kg: z
    .string()
    .trim()
    .regex(/^\d+(?:\.\d{1,3})?$/, 'Enter a weight like 0.350.')
    .refine((value) => Number(value) > 0, 'The weight must be greater than 0.'),
});

export type ProductFormValues = z.infer<typeof productSchema>;

export const PRODUCT_FIELDS = [
  'sku',
  'name',
  'description',
  'category',
  'price',
  'stock',
  'weight_kg',
] as const satisfies readonly (keyof ProductFormValues)[];

export const emptyProduct: ProductFormValues = {
  sku: '',
  name: '',
  description: '',
  category: '',
  price: '',
  stock: '0',
  weight_kg: '',
};

export function toFormValues(product: Product): ProductFormValues {
  return {
    sku: product.sku,
    name: product.name,
    description: product.description ?? '',
    category: product.category.name,
    price: product.price,
    stock: String(product.stock),
    weight_kg: product.weight_kg,
  };
}

export function toPayload(values: ProductFormValues): ProductPayload {
  return {
    ...values,
    description: values.description === '' ? null : values.description,
    stock: Number(values.stock),
  };
}
