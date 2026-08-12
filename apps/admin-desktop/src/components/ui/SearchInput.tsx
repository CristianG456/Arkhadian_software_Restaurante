import { Loader2, Search, X } from 'lucide-react'
import { cx } from '../../utils/cx'
import './SearchInput.css'

export interface SearchInputProps {
  value: string
  onChange: (value: string) => void
  placeholder?: string
  loading?: boolean
  disabled?: boolean
  className?: string
  'aria-label'?: string
}

export function SearchInput({
  value,
  onChange,
  placeholder = 'Buscar...',
  loading = false,
  disabled,
  className,
  'aria-label': ariaLabel = 'Buscar',
}: SearchInputProps) {
  return (
    <div className={cx('search-input', className)}>
      <Search size={16} className="search-input__icon" aria-hidden="true" />
      <input
        type="search"
        value={value}
        onChange={(event) => onChange(event.target.value)}
        placeholder={placeholder}
        disabled={disabled}
        aria-label={ariaLabel}
        className="search-input__field"
      />
      {loading ? (
        <Loader2 size={16} className="search-input__status search-input__status--spin" aria-hidden="true" />
      ) : (
        value.length > 0 && (
          <button
            type="button"
            className="search-input__clear"
            aria-label="Limpiar búsqueda"
            onClick={() => onChange('')}
          >
            <X size={14} aria-hidden="true" />
          </button>
        )
      )}
    </div>
  )
}
