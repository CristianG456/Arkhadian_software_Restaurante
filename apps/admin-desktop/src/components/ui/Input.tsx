import { forwardRef, useId } from 'react'
import type { InputHTMLAttributes, ReactNode } from 'react'
import { cx } from '../../utils/cx'
import { FormField } from './FormField'
import './Input.css'

export interface InputProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'className'> {
  label?: string
  description?: string
  error?: string
  icon?: ReactNode
  containerClassName?: string
  inputClassName?: string
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
  (
    {
      label,
      description,
      error,
      icon,
      id,
      required,
      containerClassName,
      inputClassName,
      ...rest
    },
    ref,
  ) => {
    const generatedId = useId()
    const inputId = id ?? generatedId

    const field = (
      <div className={cx('input-wrap', Boolean(icon) && 'input-wrap--icon', Boolean(error) && 'input-wrap--error')}>
        {icon && (
          <span className="input-wrap__icon" aria-hidden="true">
            {icon}
          </span>
        )}
        <input
          ref={ref}
          id={inputId}
          required={required}
          aria-invalid={Boolean(error) || undefined}
          className={cx('input', inputClassName)}
          {...rest}
        />
      </div>
    )

    if (!label && !description && !error) return field

    return (
      <FormField
        label={label}
        htmlFor={inputId}
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

Input.displayName = 'Input'
