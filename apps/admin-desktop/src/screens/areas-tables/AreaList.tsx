import { ArrowDown, ArrowUp, Edit3, Power, Trash2 } from 'lucide-react'
import type { Area } from '../../api/areas'
import { ActionMenu } from '../../components/ui/ActionMenu'
import { Badge } from '../../components/ui/Badge'
import { IconButton } from '../../components/ui/IconButton'
import { cx } from '../../utils/cx'

interface AreaListProps {
  areas: Area[]
  selectedId: string | null
  disabled: boolean
  reorderDisabled: boolean
  onSelect: (id: string) => void
  onEdit: (area: Area) => void
  onToggle: (area: Area) => void
  onDelete: (area: Area) => void
  onMove: (area: Area, direction: -1 | 1) => void
}

export function AreaList(props: AreaListProps) {
  const { areas, selectedId, disabled, reorderDisabled, onSelect, onEdit, onToggle, onDelete, onMove } = props

  return (
    <ul className='area-list'>
      {areas.map((area, index) => (
        <li key={area.id} className={cx('area-list__item', selectedId === area.id && 'area-list__item--selected')}>
          <button type='button' className='area-list__select' onClick={() => onSelect(area.id)}>
            <span className='area-list__title-row'>
              <strong>{area.name}</strong>
              {!area.isActive && <Badge variant='neutral' icon={null}>Desactivada</Badge>}
            </span>
            <span className='area-list__counts'>
              {area.tablesCount} mesas · {area.activeTablesCount} activas
              {area.inactiveTablesCount > 0 && ` · ${area.inactiveTablesCount} desactivadas`}
            </span>
          </button>
          <div className='area-list__actions'>
            <IconButton icon={<ArrowUp size={17} />} label={`Subir ${area.name}`} variant='ghost' tooltip={false} disabled={reorderDisabled || index === 0} onClick={() => onMove(area, -1)} />
            <IconButton icon={<ArrowDown size={17} />} label={`Bajar ${area.name}`} variant='ghost' tooltip={false} disabled={reorderDisabled || index === areas.length - 1} onClick={() => onMove(area, 1)} />
            <ActionMenu items={[
              { label: 'Editar área', icon: <Edit3 size={17} />, onSelect: () => onEdit(area), disabled },
              { label: area.isActive ? 'Desactivar área' : 'Reactivar área', icon: <Power size={17} />, onSelect: () => onToggle(area), disabled },
              { label: 'Eliminar área', icon: <Trash2 size={17} />, onSelect: () => onDelete(area), danger: true, disabled },
            ]} triggerLabel={`Acciones de ${area.name}`} />
          </div>
        </li>
      ))}
    </ul>
  )
}
