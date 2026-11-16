import { expect, test } from '@playwright/test';
import { createProduct, deleteProduct, type CreatedProduct } from './support.ts';

test.describe('shopping', () => {
  let lastUnit: CreatedProduct;
  let plenty: CreatedProduct;

  test.beforeAll(async ({ request }) => {
    lastUnit = await createProduct(request, { name: 'E2E Last Lamp', price: '10.00', stock: 1 });
    plenty = await createProduct(request, { name: 'E2E Plenty Mug', price: '4.50', stock: 20 });
  });

  test.afterAll(async ({ request }) => {
    await deleteProduct(request, lastUnit);
    await deleteProduct(request, plenty);
  });

  test('finds a product, pays for it and sees the stock run out', async ({ page }) => {
    await page.goto('/');
    await page.getByLabel('Search products').fill(lastUnit.sku);
    await page.getByRole('button', { name: 'Search' }).click();

    await expect(page.getByRole('link', { name: lastUnit.name })).toBeVisible();
    await page.getByRole('button', { name: 'Add to cart' }).click();
    await expect(page.getByRole('button', { name: 'All units in cart' })).toBeDisabled();

    await page.getByRole('link', { name: /^Cart/ }).click();
    await page.getByRole('link', { name: 'Checkout' }).click();
    await page.getByLabel('Full name').fill('Ada Lovelace');
    await page.getByLabel('Email').fill('ada@example.com');
    await page.getByRole('button', { name: 'Pay $10.00' }).click();

    await expect(page.getByRole('heading', { name: /your order is confirmed/i })).toBeVisible();
    await expect(page.getByText(/^ORD-\d{8}-[A-Z0-9]{6}$/)).toBeVisible();
    await expect(page.getByText('Total paid')).toBeVisible();

    await page.goto(`/products/${lastUnit.id}`);
    await expect(page.getByText('Out of stock')).toBeVisible();
  });

  test('explains a declined payment and keeps the cart', async ({ page }) => {
    await page.goto(`/products/${plenty.id}`);
    await page.getByRole('button', { name: 'Add to cart' }).click();

    await page.goto('/checkout');
    await page.getByLabel('Full name').fill('Declined Customer');
    await page.getByLabel('Email').fill('decline@example.com');
    await page.getByRole('button', { name: 'Pay $4.50' }).click();

    await expect(page.getByRole('alert')).toHaveText(/payment was declined/i);
    await expect(page.getByRole('link', { name: /^Cart\s*1$/ })).toBeVisible();

    await page.goto(`/products/${plenty.id}`);
    await expect(page.getByText('20 in stock')).toBeVisible();
  });
});
