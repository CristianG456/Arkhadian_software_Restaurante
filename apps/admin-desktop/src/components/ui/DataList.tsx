import type { ReactNode } from 'react'
import { cx } from '../../utils/cx'
import { LoadingState } from './Spinner'
import './DataList.css'

export interface DataListColumn<T> {
  key: string
  header: string
  render: (row: T) => ReactNode
  width?: string
  align?: 'left' | 'right' | 'center'
}

export interface DataListProps<T> {
  columns: DataListColumn<T>[]
  rows: T[]
  rowKey: (row: T) => string
  loading?: boolean
  emptyState?: ReactNode
  onRowClick?: (row: T) => void
  selectedRowKey?: string
  className?: string
}

/**
 * The base for future listings (mesas, pedidos, productos, ...) - plain
 * touch-height rows, not a data-grid. Column definitions are generic;
 * this component has no idea what a "row" represents.
 */
export function DataList<T>({
  columns,
  rows,
  rowKey,
  loading = false,
  emptyState,
  onRowClick,
  selectedRowKey,
  className,
}: DataListProps<T>) {
  if (loading) return <LoadingState label="Cargando..." />
  if (rows.length === 0 && emptyState) return <>{emptyState}</>

  return (
    <div className={cx('data-list', className)}>
      <table className="data-list__table">
        <thead>
          <tr>
            {columns.map((column) => (
              <th
                key={column.key}
                style={{ width: column.width, textAlign: column.align ?? 'left' }}
              >
                {column.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => {
            const key = rowKey(row)
            const clickable = Boolean(onRowClick)
            return (
              <tr
                key={key}
                className={cx(
                  clickable && 'data-list__row--clickable',
                  selectedRowKey === key && 'data-list__row--selected',
                )}
                onClick={clickable ? () => onRowClick?.(row) : undefined}
                tabIndex={clickable ? 0 : undefined}
                onKeyDown={
                  clickable
                    ? (event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                          event.preventDefault()
                          onRowClick?.(row)
                        }
                      }
                    : undefined
                }
              >
                {columns.map((column) => (
                  <td key={column.key} style={{ textAlign: column.align ?? 'left' }}>
                    {column.render(row)}
                  </td>
                ))}
              </tr>
            )
          })}
        </tbody>
      </table>
    </div>
  )
}
