import { forwardRef, useId } from 'react'
import type { InputHTMLAttributes } from 'react'
import './Switch.css'

export interface SwitchProps
  extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'className'> {
  label: string
  description?: string
}

export const Switch = forwardRef<HTMLInputElement, SwitchProps>(
  ({ label, description, id, ...rest }, ref) => {
    const generatedId = useId()
    const switchId = id ?? generatedId

    return (
      <label htmlFor={switchId} className="switch">
        <span className="switch__text">
          <span className="switch__label">{label}</span>
          {description && <span className="switch__description">{description}</span>}
        </span>
        <span className="switch__control">
          <input
            ref={ref}
            type="checkbox"
            role="switch"
            id={switchId}
            className="switch__input"
            {...rest}
          />
          <span className="switch__track">
            <span className="switch__thumb" />
          </span>
        </span>
      </label>
    )
  },
)

Switch.displayName = 'Switch'
