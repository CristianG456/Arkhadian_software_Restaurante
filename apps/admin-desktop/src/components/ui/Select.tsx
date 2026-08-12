import { forwardRef, useId } from 'react'
import type { SelectHTMLAttributes } from 'react'
import { ChevronDown } from 'lucide-react'
import { cx } from '../../utils/cx'
import { FormField } from './FormField'
import './Select.css'

export interface SelectOption {
  value: string
  label: string
  disabled?: boolean
}

export interface SelectProps
  extends Omit<SelectHTMLAttributes<HTMLSelectElement>, 'className'> {
  label?: string
  description?: string
  error?: string
  options: SelectOption[]
  placeholder?: string
  containerClassName?: string
}

/**
 * A styled wrapper around the native <select> - not a custom listbox.
 * The native control already handles touch, keyboard, and platform
 * conventions better than a hand-rolled one would.
 */
export const Select = forwardRef<HTMLSelectElement, SelectProps>(
  (
    { label, description, error, options, placeholder, id, required, containerClassName, value, defaultValue, ...rest },
    ref,
  ) => {
    const generatedId = useId()
    const selectId = id ?? generatedId
    const isUncontrolled = value === undefined && defaultValue === undefined

    const field = (
      <div className={cx('select-wrap', error && 'select-wrap--error')}>
        <select
          ref={ref}
          id={selectId}
          required={required}
          aria-invalid={Boolean(error) || undefined}
          value={value}
          defaultValue={isUncontrolled ? '' : defaultValue}
          className="select"
          {...rest}
        >
          {placeholder && (
            <option value="" disabled>
              {placeholder}
            </option>
          )}
          {options.map((option) => (
            <option key={option.value} value={option.value} disabled={option.disabled}>
              {option.label}
            </option>
          ))}
        </select>
        <ChevronDown size={16} className="select-wrap__icon" aria-hidden="true" />
      </div>
    )

    if (!label && !description && !error) return field

    return (
      <FormField
        label={label}
        htmlFor={selectId}
        description={description}
        error={error}
        required={required}
        className={containerClassName}
      >
        {field}
      </FormField>
    )
  },
)

Select.displayName = 'Select'
