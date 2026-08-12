import { Check, Delete } from 'lucide-react'
import { cx } from '../../utils/cx'
import { applyKeypadKey } from './numericKeypad.logic'
import type { KeypadOptions } from './numericKeypad.logic'
import './NumericKeypad.css'

export interface NumericKeypadProps extends KeypadOptions {
  value: string
  onChange: (value: string) => void
  onConfirm?: (value: string) => void
  confirmLabel?: string
  disabled?: boolean
  className?: string
}

const DIGIT_ROWS = [
  ['1', '2', '3'],
  ['4', '5', '6'],
  ['7', '8', '9'],
] as const

/**
 * Controlled, presentation-only - it never touches caja/cash logic
 * itself. Callers own `value` and decide what onConfirm means.
 *
 * Default layout matches the spec's 4x3 grid (1-9, backspace/0/confirm).
 * With allowDecimal, the bottom row becomes ./0/backspace and confirm
 * moves to its own full-width row so it's never confused with "0".
 */
export function NumericKeypad({
  value,
  onChange,
  onConfirm,
  maxLength,
  allowDecimal = false,
  confirmLabel = 'Confirmar',
  disabled = false,
  className,
}: NumericKeypadProps) {
  function press(key: Parameters<typeof applyKeypadKey>[1]) {
    onChange(applyKeypadKey(value, key, { maxLength, allowDecimal }))
  }

  const backspaceButton = (
    <button
      type="button"
      className="keypad__key keypad__key--muted"
      disabled={disabled || value.length === 0}
      aria-label="Borrar"
      onClick={() => press('backspace')}
    >
      <Delete size={20} aria-hidden="true" />
    </button>
  )

  const zeroButton = (
    <button type="button" className="keypad__key" disabled={disabled} onClick={() => press('0')}>
      0
    </button>
  )

  const confirmButton = (
    <button
      type="button"
      className="keypad__key keypad__key--confirm"
      disabled={disabled || value.length === 0}
      aria-label={confirmLabel}
      onClick={() => onConfirm?.(value)}
    >
      <Check size={20} aria-hidden="true" />
    </button>
  )

  return (
    <div className={cx('keypad', className)}>
      {DIGIT_ROWS.map((row) => (
        <div className="keypad__row" key={row.join('')}>
          {row.map((digit) => (
            <button
              key={digit}
              type="button"
              className="keypad__key"
              disabled={disabled}
              onClick={() => press(digit)}
            >
              {digit}
            </button>
          ))}
        </div>
      ))}

      {allowDecimal ? (
        <>
          <div className="keypad__row">
            <button
              type="button"
              className="keypad__key"
              disabled={disabled}
              aria-label="Punto decimal"
              onClick={() => press('decimal')}
            >
              .
            </button>
            {zeroButton}
            {backspaceButton}
          </div>
          <div className="keypad__row">
            <button
              type="button"
              className="keypad__key keypad__key--confirm keypad__key--wide"
              disabled={disabled || value.length === 0}
              onClick={() => onConfirm?.(value)}
            >
              <Check size={20} aria-hidden="true" />
              {confirmLabel}
            </button>
          </div>
        </>
      ) : (
        <div className="keypad__row">
          {backspaceButton}
          {zeroButton}
          {confirmButton}
        </div>
      )}
    </div>
  )
}
