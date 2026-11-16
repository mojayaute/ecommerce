const formatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });

export function toCents(amount: string): number {
  const [whole = '0', fraction = ''] = amount.trim().split('.');

  return Number(whole) * 100 + Number(fraction.padEnd(2, '0').slice(0, 2));
}

export function fromCents(cents: number): string {
  return `${Math.floor(cents / 100)}.${String(cents % 100).padStart(2, '0')}`;
}

export function formatMoney(amount: string): string {
  return formatter.format(toCents(amount) / 100);
}
