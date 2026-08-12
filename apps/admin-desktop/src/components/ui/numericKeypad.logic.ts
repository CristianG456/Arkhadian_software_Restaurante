export type KeypadDigit = '0' | '1' | '2' | '3' | '4' | '5' | '6' | '7' | '8' | '9'
export type KeypadKey = KeypadDigit | 'backspace' | 'clear' | 'decimal'

export interface KeypadOptions {
  /** Max digit count, decimal point excluded. */
  maxLength?: number
  allowDecimal?: boolean
}

/**
 * Pure reducer for numeric-keypad input, kept separate from the component
 * so it's testable without rendering anything. No leading zeros
 * ("0" + "5" -> "5", not "05"), single decimal point, digit-count cap
 * enforced before the decimal point (so 100.00 with maxLength 3 truncates
 * the whole part, never the cents).
 */
export function applyKeypadKey(value: string, key: KeypadKey, options: KeypadOptions = {}): string {
  const { maxLength, allowDecimal = false } = options

  if (key === 'clear') return ''

  if (key === 'backspace') return value.slice(0, -1)

  if (key === 'decimal') {
    if (!allowDecimal || value.includes('.')) return value
    return value.length === 0 ? '0.' : `${value}.`
  }

  // Digit
  if (value === '0') return key === '0' ? value : key
  if (value === '') return key

  const [wholePart = ''] = value.split('.')
  if (maxLength && !value.includes('.') && wholePart.length >= maxLength) return value

  return value + key
}
