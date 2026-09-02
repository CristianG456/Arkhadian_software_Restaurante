import { ArrowDown, ArrowUp, Edit3, Layers3, MoveRight, Plus, Power, QrCode, Trash2 } from 'lucide-react'
import { useMemo, useState } from 'react'
import type { Area, RestaurantTable } from '../../api/areas'
import { ActionMenu } from '../../components/ui/ActionMenu'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { EmptyState } from '../../components/ui/EmptyState'
import { SearchInput } from '../../components/ui/SearchInput'
import { cx } from '../../utils/cx'
import { filterTables, tableEmptyStateTitle } from './areasTables.logic'
import type { StatusFilter } from './areasTables.logic'

interface TableGridProps {
  area: Area
  disabled: boolean
  onAdd: () => void
  onBulkAdd: () => void
  onEdit: (table: RestaurantTable) => void
  onMoveArea: (table: RestaurantTable) => void
  onQr: (table: RestaurantTable) => void
  onToggle: (table: RestaurantTable) => void
  onDelete: (table: RestaurantTable) => void
  onReorder: (table: RestaurantTable, direction: -1 | 1) => void
}

export function TableGrid(props: TableGridProps) {
  const { area, disabled, onAdd, onBulkAdd, onEdit, onMoveArea, onQr, onToggle, onDelete, onReorder } = props
  const [query, setQuery] = useState('')
  const [filter, setFilter] = useState<StatusFilter>('active')
  const tables = useMemo(() => filterTables(area.tables, query, filter), [area.tables, query, filter])

  return (
    <section className='tables-panel' aria-labelledby='selected-area-title'>
      <header className='tables-panel__header'>
        <div>
          <div className='tables-panel__title-row'>
            <h2 id='selected-area-title'>{area.name}</h2>
            {!area.isActive && <Badge variant='neutral' icon={null}>Área desactivada</Badge>}
          </div>
          <p>{area.tablesCount} mesas · {area.activeTablesCount} activas · {area.inactiveTablesCount} desactivadas</p>
        </div>
        <div className='tables-panel__primary-actions'>
          <Button variant='outline' icon={<Layers3 size={18} />} onClick={onBulkAdd} disabled={disabled}>Crear varias</Button>
          <Button icon={<Plus size={18} />} onClick={onAdd} disabled={disabled}>Agregar mesa</Button>
        </div>
      </header>

      <div className='tables-toolbar'>
        <SearchInput value={query} onChange={setQuery} placeholder='Buscar mesa...' aria-label='Buscar mesa' />
        <div className='status-filter' aria-label='Filtrar mesas'>
          {(['active', 'inactive', 'all'] as StatusFilter[]).map((value) => (
            <button key={value} type='button' className={cx('status-filter__button', filter === value && 'status-filter__button--active')} onClick={() => setFilter(value)}>
              {value === 'active' ? 'Activas' : value === 'inactive' ? 'Desactivadas' : 'Todas'}
            </button>
          ))}
        </div>
      </div>

      {tables.length === 0 ? (
        <EmptyState
          icon={<Layers3 size={28} />}
          title={tableEmptyStateTitle(area.tables.length, query, filter)}
          description={area.tables.length === 0 ? 'Agrega una mesa o crea un rango numérico en segundos.' : 'Cambia la búsqueda o el filtro para ver otros resultados.'}
          action={area.tables.length === 0 ? <Button icon={<Plus size={18} />} onClick={onAdd} disabled={disabled}>Agregar primera mesa</Button> : undefined}
        />
      ) : (
        <div className='table-grid'>
          {tables.map((table) => {
            const originalIndex = area.tables.findIndex((item) => item.id === table.id)
            return (
              <article key={table.id} className={cx('table-card', !table.isActive && 'table-card--inactive')}>
                <div>
                  <h3>{table.displayName}</h3>
                  <Badge variant={table.isActive ? 'success' : 'neutral'} icon={null}>
                    {table.isActive ? 'Activa' : 'Desactivada'}
                  </Badge>
                </div>
                <ActionMenu align='end' triggerLabel={`Acciones de ${table.displayName}`} items={[
                  { label: 'Editar', icon: <Edit3 size={17} />, onSelect: () => onEdit(table), disabled },
                  { label: 'Mover a otra área', icon: <MoveRight size={17} />, onSelect: () => onMoveArea(table), disabled },
                  { label: 'QR de mesa', icon: <QrCode size={17} />, onSelect: () => onQr(table), disabled },
                  { label: 'Subir en el orden', icon: <ArrowUp size={17} />, onSelect: () => onReorder(table, -1), disabled: disabled || originalIndex === 0 },
                  { label: 'Bajar en el orden', icon: <ArrowDown size={17} />, onSelect: () => onReorder(table, 1), disabled: disabled || originalIndex === area.tables.length - 1 },
                  { label: table.isActive ? 'Desactivar mesa' : 'Reactivar mesa', icon: <Power size={17} />, onSelect: () => onToggle(table), disabled },
                  { label: 'Eliminar mesa', icon: <Trash2 size={17} />, onSelect: () => onDelete(table), danger: true, disabled },
                ]} />
              </article>
            )
          })}
        </div>
      )}
    </section>
  )
}
