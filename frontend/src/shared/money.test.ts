import { describe, expect, it } from 'vitest';
import { formatMoney, fromCents, toCents } from './money';

describe('money', () => {
  it('converts decimal strings to integer cents', () => {
    expect(toCents('29.99')).toBe(2999);
    expect(toCents('0.1')).toBe(10);
    expect(toCents('5')).toBe(500);
  });

  it('adds amounts without floating point errors', () => {
    expect(fromCents(toCents('0.10') + toCents('0.20'))).toBe('0.30');
    expect(fromCents(toCents('19.99') * 3)).toBe('59.97');
  });

  it('formats amounts as US dollars', () => {
    expect(formatMoney('1234.5')).toBe('$1,234.50');
  });
});
