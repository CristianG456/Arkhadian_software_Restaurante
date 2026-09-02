import { describe, expect, it } from 'vitest'
import { formatCop, normalizeCatalogPrice, normalizeMoneyInput } from './catalog.logic'

describe('catalog money', () => {
  it('formats decimal strings without floats', () => expect(formatCop('999999.99')).toBe('$999.999'))
  it('normalizes COP input', () => expect(normalizeMoneyInput('$ 29.000')).toBe('29000'))
  it('preserves exact decimal strings', () => expect(normalizeCatalogPrice('3500.50')).toBe('3500.50'))
  it('accepts formatted whole COP', () => expect(normalizeCatalogPrice('$ 3.500')).toBe('3500'))
})
