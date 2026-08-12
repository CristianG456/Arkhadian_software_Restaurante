import { useId } from 'react'
import { cx } from '../../utils/cx'
import './RadioGroup.css'

export interface RadioOption {
  value: string
  label: string
  description?: string
  disabled?: boolean
}

export interface RadioGroupProps {
  name?: string
  label?: string
  options: RadioOption[]
  value: string
  onChange: (value: string) => void
  disabled?: boolean
  direction?: 'vertical' | 'horizontal'
  className?: string
}

export function RadioGroup({
  name,
  label,
  options,
  value,
  onChange,
  disabled,
  direction = 'vertical',
  className,
}: RadioGroupProps) {
  const generatedName = useId()
  const groupName = name ?? generatedName

  return (
    <div className={cx('radio-group', className)}>
      {label && <span className="radio-group__label">{label}</span>}
      <div
        role="radiogroup"
        aria-label={label}
        className={cx('radio-group__options', `radio-group__options--${direction}`)}
      >
        {options.map((option) => (
          <label key={option.value} className="radio">
            <span className="radio__control">
              <input
                type="radio"
                name={groupName}
                value={option.value}
                checked={value === option.value}
                disabled={disabled || option.disabled}
                onChange={() => onChange(option.value)}
                className="radio__input"
              />
              <span className="radio__mark" />
            </span>
            <span className="radio__text">
              <span className="radio__label">{option.label}</span>
              {option.description && <span className="radio__description">{option.description}</span>}
            </span>
          </label>
        ))}
      </div>
    </div>
  )
}
