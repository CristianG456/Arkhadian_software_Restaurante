import { describe, expect, it } from 'vitest'
import { createUlid, moneyMinor } from './orders.logic'
describe('orders helpers', () => {
  it('creates a valid ULID-shaped id', () => expect(createUlid(0, () => 0)).toMatch(/^[0-9A-HJKMNP-TV-Z]{26}$/))
  it('parses decimal money without floats', () => { expect(moneyMinor('12500.50')).toBe(1250050); expect(moneyMinor('9')).toBe(900) })
})
