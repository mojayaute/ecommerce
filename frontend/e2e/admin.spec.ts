import { fileURLToPath } from 'node:url';
import { expect, test } from '@playwright/test';
import { uniqueSku } from './support.ts';

const challengeCsv =
  process.env.CSV_FIXTURE ??
  fileURLToPath(new URL('../../backend/tests/Fixtures/products.csv', import.meta.url));

test.describe('administration', () => {
  test('imports the challenge CSV and explains every rejected row', async ({ page }) => {
    await page.goto('/admin/imports');
    await page.getByLabel('CSV file').setInputFiles(challengeCsv);
    await page.getByRole('button', { name: 'Import' }).click();

    await expect(page.getByRole('heading', { name: 'products.csv' })).toBeVisible();
    await expect(page.getByText('97 data rows in the file')).toBeVisible();
    await expect(page.getByRole('tab', { name: '13 Rejected' })).toHaveAttribute(
      'aria-selected',
      'true',
    );
    await expect(page.getByRole('tab', { name: '3 Skipped' })).toBeVisible();

    const yogaMat = page.getByRole('row', { name: /YM-015/ });
    await expect(yogaMat).toContainText('The price field must be a number.');

    const script = page.getByRole('row', { name: /XS-001/ });
    await expect(script).toContainText("<script>alert('xss')</script>");
    await expect(script).toContainText('must be plain text without markup or code');
  });

  test('creates, edits and deletes a product', async ({ page }) => {
    const sku = uniqueSku('CRUD');

    await page.goto('/admin/products/new');
    await page.getByRole('button', { name: 'Save product' }).click();
    await expect(page.getByText('Enter a SKU.')).toBeVisible();

    await page.getByLabel('SKU').fill(sku);
    await page.getByLabel('Name').fill('E2E Desk Fan');
    await page.getByLabel('Category').fill('E2E');
    await page.getByLabel('Price (USD)').fill('25.00');
    await page.getByLabel('Stock').fill('8');
    await page.getByLabel('Weight (kg)').fill('1.2');
    await page.getByRole('button', { name: 'Save product' }).click();

    await page.getByLabel('Search products').fill(sku);
    await page.getByRole('button', { name: 'Search' }).click();
    const row = page.getByRole('row', { name: new RegExp(sku) });
    await expect(row).toContainText('$25.00');

    await row.getByRole('link', { name: 'Edit E2E Desk Fan' }).click();
    await page.getByLabel('Price (USD)').fill('19.99');
    await page.getByRole('button', { name: 'Save product' }).click();

    await page.getByLabel('Search products').fill(sku);
    await page.getByRole('button', { name: 'Search' }).click();
    await expect(page.getByRole('row', { name: new RegExp(sku) })).toContainText('$19.99');

    page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('button', { name: 'Delete E2E Desk Fan' }).click();
    await expect(page.getByText('No products found.')).toBeVisible();
  });
});
