import type { ReactNode } from 'react'
import { cx } from '../../utils/cx'
import './FormField.css'

export interface FormFieldProps {
  label?: string
  htmlFor?: string
  description?: string
  error?: string
  required?: boolean
  children: ReactNode
  className?: string
}

/**
 * The one label/description/error layout every field control (Input,
 * Textarea, Select, ...) is built on, so that layout never gets
 * reimplemented slightly differently in each one.
 */
export function FormField({
  label,
  htmlFor,
  description,
  error,
  required,
  children,
  className,
}: FormFieldProps) {
  return (
    <div className={cx('form-field', className)}>
      {label && (
        <label className="form-field__label" htmlFor={htmlFor}>
          {label}
          {required && (
            <span className="form-field__required" aria-hidden="true">
              *
            </span>
          )}
        </label>
      )}
      {children}
      {error ? (
        <p className="form-field__error" role="alert">
          {error}
        </p>
      ) : (
        description && <p className="form-field__description">{description}</p>
      )}
    </div>
  )
}
