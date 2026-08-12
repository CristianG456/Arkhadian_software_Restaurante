import { forwardRef, useId } from 'react'
import type { InputHTMLAttributes } from 'react'
import { Check } from 'lucide-react'
import './Checkbox.css'

export interface CheckboxProps
  extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'className'> {
  label: string
  description?: string
}

/** A real <input type="checkbox"> underneath - keyboard and screen reader
 * support come from the platform, not reimplemented here. */
export const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(
  ({ label, description, id, ...rest }, ref) => {
    const generatedId = useId()
    const checkboxId = id ?? generatedId

    return (
      <label htmlFor={checkboxId} className="checkbox">
        <span className="checkbox__control">
          <input ref={ref} type="checkbox" id={checkboxId} className="checkbox__input" {...rest} />
          <Check size={14} className="checkbox__mark" aria-hidden="true" />
        </span>
        <span className="checkbox__text">
          <span className="checkbox__label">{label}</span>
          {description && <span className="checkbox__description">{description}</span>}
        </span>
      </label>
    )
  },
)

Checkbox.displayName = 'Checkbox'
