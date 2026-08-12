import { describe, expect, it } from 'vitest'
import { applyKeypadKey } from './numericKeypad.logic'

describe('applyKeypadKey', () => {
  it('appends digits in order', () => {
    let value = ''
    value = applyKeypadKey(value, '1')
    value = applyKeypadKey(value, '2')
    value = applyKeypadKey(value, '3')
    expect(value).toBe('123')
  })

  it('never produces a leading zero', () => {
    expect(applyKeypadKey('', '0')).toBe('0')
    expect(applyKeypadKey('0', '5')).toBe('5')
    expect(applyKeypadKey('0', '0')).toBe('0')
  })

  it('backspace removes the last character', () => {
    expect(applyKeypadKey('123', 'backspace')).toBe('12')
    expect(applyKeypadKey('1', 'backspace')).toBe('')
    expect(applyKeypadKey('', 'backspace')).toBe('')
  })

  it('clear resets to empty regardless of current value', () => {
    expect(applyKeypadKey('98765', 'clear')).toBe('')
    expect(applyKeypadKey('', 'clear')).toBe('')
  })

  it('enforces maxLength on the whole-number part', () => {
    expect(applyKeypadKey('99', '9', { maxLength: 2 })).toBe('99')
    expect(applyKeypadKey('9', '9', { maxLength: 2 })).toBe('99')
  })

  it('ignores the decimal key unless allowDecimal is set', () => {
    expect(applyKeypadKey('12', 'decimal')).toBe('12')
    expect(applyKeypadKey('12', 'decimal', { allowDecimal: true })).toBe('12.')
  })

  it('starts a decimal value from empty as "0."', () => {
    expect(applyKeypadKey('', 'decimal', { allowDecimal: true })).toBe('0.')
  })

  it('allows only one decimal point', () => {
    expect(applyKeypadKey('12.5', 'decimal', { allowDecimal: true })).toBe('12.5')
  })

  it('does not let maxLength truncate digits already after the decimal point', () => {
    expect(applyKeypadKey('12.34', '5', { maxLength: 2, allowDecimal: true })).toBe('12.345')
  })

  it('appends digits after the decimal point normally', () => {
    expect(applyKeypadKey('12.', '5', { allowDecimal: true })).toBe('12.5')
  })
})
