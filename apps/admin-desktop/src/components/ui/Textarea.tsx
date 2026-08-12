import { forwardRef, useId } from 'react'
import type { TextareaHTMLAttributes } from 'react'
import { cx } from '../../utils/cx'
import { FormField } from './FormField'
import './Textarea.css'

export interface TextareaProps
  extends Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'className'> {
  label?: string
  description?: string
  error?: string
  containerClassName?: string
  textareaClassName?: string
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ label, description, error, id, required, containerClassName, textareaClassName, rows = 3, ...rest }, ref) => {
    const generatedId = useId()
    const textareaId = id ?? generatedId

    const field = (
      <textarea
        ref={ref}
        id={textareaId}
        required={required}
        rows={rows}
        aria-invalid={Boolean(error) || undefined}
        className={cx('textarea', error && 'textarea--error', textareaClassName)}
        {...rest}
      />
    )

    if (!label && !description && !error) return field

    return (
      <FormField
        label={label}
        htmlFor={textareaId}
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

Textarea.displayName = 'Textarea'
