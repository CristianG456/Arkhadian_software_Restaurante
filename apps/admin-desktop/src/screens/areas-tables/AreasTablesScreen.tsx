import { Layers3, Plus, RefreshCw } from 'lucide-react'
import { useEffect, useMemo, useRef, useState } from 'react'
import type { Area, RestaurantTable } from '../../api/areas'
import { useSystemStatusContext } from '../../app/SystemStatusContext'
import { Alert } from '../../components/ui/Alert'
import { Button } from '../../components/ui/Button'
import { ConfirmDialog } from '../../components/ui/ConfirmDialog'
import { EmptyState } from '../../components/ui/EmptyState'
import { LoadingState } from '../../components/ui/Spinner'
import { useToast } from '../../components/ui/ToastProvider'
import { getApiErrorMessage, useAreas } from '../../hooks/useAreas'
import { AreaDialog } from './AreaDialog'
import { AreaList } from './AreaList'
import { BulkTablesDialog } from './BulkTablesDialog'
import { MoveTableDialog } from './MoveTableDialog'
import { TableDialog } from './TableDialog'
import { TableGrid } from './TableGrid'
import { TableQrDialog } from './TableQrDialog'
import { filterAreas, moveId } from './areasTables.logic'
import type { StatusFilter } from './areasTables.logic'
import './AreasTablesScreen.css'

interface Confirmation {
  title: string
  description: string
  label: string
  danger?: boolean
  action: () => Promise<void>
}

export function AreasTablesScreen() {
  const model = useAreas()
  const system = useSystemStatusContext()
  const toast = useToast()
  const [selectedId, setSelectedId] = useState<string | null>(null)
  const [areaFilter, setAreaFilter] = useState<StatusFilter>('active')
  const [areaDialogOpen, setAreaDialogOpen] = useState(false)
  const [editingArea, setEditingArea] = useState<Area | null>(null)
  const [tableDialogOpen, setTableDialogOpen] = useState(false)
  const [editingTable, setEditingTable] = useState<RestaurantTable | null>(null)
  const [bulkOpen, setBulkOpen] = useState(false)
  const [movingTable, setMovingTable] = useState<RestaurantTable | null>(null)
  const [qrTable, setQrTable] = useState<RestaurantTable | null>(null)
  const [confirmation, setConfirmation] = useState<Confirmation | null>(null)
  const [availabilityAlertDismissed, setAvailabilityAlertDismissed] = useState(false)

  const visibleAreas = useMemo(() => filterAreas(model.areas, areaFilter), [model.areas, areaFilter])
  const selectedArea = visibleAreas.find((area) => area.id === selectedId) ?? visibleAreas[0] ?? null
  const databaseOnline = system.connectivity === 'ONLINE' && system.data?.database.status === 'connected'
  const writeDisabled = !databaseOnline || model.saving
  const availabilityError = !databaseOnline
    ? system.connectivity === 'ONLINE'
      ? 'La base de datos local no está disponible.'
      : 'Restaurant Core no está disponible.'
    : model.error
  const dataUnavailable = model.areas.length === 0 && Boolean(availabilityError)
  const refreshAreas = model.refresh
  const wasDatabaseOnline = useRef(databaseOnline)
  const qrArea = qrTable ? model.areas.find((area) => area.id === qrTable.areaId) ?? null : null

  useEffect(() => {
    const recovered = databaseOnline && !wasDatabaseOnline.current
    wasDatabaseOnline.current = databaseOnline
    if (!recovered) return

    const refreshId = window.setTimeout(() => {
      setAvailabilityAlertDismissed(false)
      void refreshAreas()
    }, 0)
    return () => window.clearTimeout(refreshId)
  }, [databaseOnline, refreshAreas])

  function retryLoad() {
    setAvailabilityAlertDismissed(false)
    model.clearError()
    system.refresh()
    void model.refresh()
  }

  async function perform(action: () => Promise<unknown>, successMessage: string) {
    try {
      await action()
      toast.success(successMessage)
      return true
    } catch (error) {
      toast.error(getApiErrorMessage(error))
      return false
    }
  }

  function requestAreaToggle(area: Area) {
    const action = async () => {
      if (await perform(() => model.updateArea(area.id, { isActive: !area.isActive }), area.isActive ? 'Área desactivada.' : 'Área reactivada.')) {
        setConfirmation(null)
      }
    }
    if (!area.isActive) void action()
    else setConfirmation({ title: `Desactivar ${area.name}`, description: 'Sus mesas y datos se conservarán y podrás reactivarla después.', label: 'Desactivar área', action })
  }

  function requestTableToggle(table: RestaurantTable) {
    const action = async () => {
      if (await perform(() => model.updateTable(table.id, { isActive: !table.isActive }), table.isActive ? 'Mesa desactivada.' : 'Mesa reactivada.')) {
        setConfirmation(null)
      }
    }
    if (!table.isActive) void action()
    else setConfirmation({ title: `Desactivar ${table.displayName}`, description: 'La mesa permanecerá registrada y podrás reactivarla después.', label: 'Desactivar mesa', action })
  }

  async function moveArea(area: Area, direction: -1 | 1) {
    const ids = moveId(model.areas.map((item) => item.id), area.id, direction)
    await perform(() => model.reorderAreas(ids), 'Orden de áreas actualizado.')
  }

  async function moveTable(table: RestaurantTable, direction: -1 | 1) {
    if (!selectedArea) return
    const ids = moveId(selectedArea.tables.map((item) => item.id), table.id, direction)
    await perform(() => model.reorderTables(selectedArea.id, ids), 'Orden de mesas actualizado.')
  }

  return (
    <div className='areas-tables-screen'>
      <header className='areas-tables-screen__header'>
        <div>
          <h1>Áreas y mesas</h1>
          <p>Organiza los espacios y las mesas del restaurante.</p>
        </div>
        <Button icon={<Plus size={18} />} disabled={writeDisabled} onClick={() => {
          setEditingArea(null)
          setAreaDialogOpen(true)
        }}>Nueva área</Button>
      </header>

      {availabilityError && !availabilityAlertDismissed && (
        <Alert
          variant='danger'
          title={databaseOnline ? 'No pudimos cargar las áreas' : 'Servicio local no disponible'}
          onDismiss={() => setAvailabilityAlertDismissed(true)}
        >
          <span>{availabilityError}</span>
          <Button variant='outline' size='sm' icon={<RefreshCw size={16} />} onClick={retryLoad}>Reintentar</Button>
        </Alert>
      )}

      {model.loading ? (
        <LoadingState label='Cargando áreas y mesas...' />
      ) : dataUnavailable ? (
        <EmptyState
          icon={<Layers3 size={30} />}
          title='Áreas no disponibles'
          description='Conecta la base de datos local para consultar y administrar las áreas.'
          action={<Button variant='outline' icon={<RefreshCw size={18} />} onClick={retryLoad}>Reintentar conexión</Button>}
        />
      ) : model.areas.length === 0 ? (
        <EmptyState
          icon={<Layers3 size={30} />}
          title='Aún no has creado áreas'
          description='Las áreas te permiten organizar las mesas por espacios como salón, VIP o terraza.'
          action={<Button icon={<Plus size={18} />} disabled={writeDisabled} onClick={() => {
            setEditingArea(null)
            setAreaDialogOpen(true)
          }}>Crear primera área</Button>}
        />
      ) : (
        <div className='areas-tables-layout'>
          <aside className='areas-panel' aria-label='Áreas del restaurante'>
            <div className='areas-panel__header'>
              <h2>Áreas</h2>
              <div className='status-filter status-filter--compact' aria-label='Filtrar áreas'>
                {(['active', 'inactive', 'all'] as StatusFilter[]).map((value) => (
                  <button key={value} type='button' className={areaFilter === value ? 'status-filter__button status-filter__button--active' : 'status-filter__button'} onClick={() => setAreaFilter(value)}>
                    {value === 'active' ? 'Activas' : value === 'inactive' ? 'Inactivas' : 'Todas'}
                  </button>
                ))}
              </div>
            </div>
            {visibleAreas.length === 0 ? (
              <p className='areas-panel__empty'>No hay áreas en este filtro.</p>
            ) : (
              <AreaList
                areas={visibleAreas}
                selectedId={selectedArea?.id ?? null}
                disabled={writeDisabled}
                reorderDisabled={writeDisabled || areaFilter !== 'all'}
                onSelect={setSelectedId}
                onEdit={(area) => {
                  setEditingArea(area)
                  setAreaDialogOpen(true)
                }}
                onToggle={requestAreaToggle}
                onDelete={(area) => setConfirmation({
                  title: `Eliminar ${area.name}`,
                  description: area.tablesCount > 0 ? `Esta área contiene ${area.tablesCount} mesas y el backend no permitirá eliminarla. Puedes desactivarla.` : 'Esta acción eliminará el área vacía de forma permanente.',
                  label: 'Eliminar área',
                  danger: true,
                  action: async () => {
                    if (await perform(() => model.deleteArea(area.id), 'Área eliminada.')) setConfirmation(null)
                  },
                })}
                onMove={(area, direction) => void moveArea(area, direction)}
              />
            )}
            {areaFilter !== 'all' && <p className='areas-panel__order-hint'>Selecciona “Todas” para cambiar el orden global.</p>}
          </aside>

          {selectedArea && (
            <TableGrid
              area={selectedArea}
              disabled={writeDisabled}
              onAdd={() => {
                setEditingTable(null)
                setTableDialogOpen(true)
              }}
              onBulkAdd={() => setBulkOpen(true)}
              onEdit={(table) => {
                setEditingTable(table)
                setTableDialogOpen(true)
              }}
              onMoveArea={setMovingTable}
              onQr={setQrTable}
              onToggle={requestTableToggle}
              onDelete={(table) => setConfirmation({
                title: `Eliminar ${table.displayName}`,
                description: 'Esta acción eliminará la mesa de forma permanente. En fases futuras se bloqueará si tiene sesiones relacionadas.',
                label: 'Eliminar mesa',
                danger: true,
                action: async () => {
                  if (await perform(() => model.deleteTable(table.id), 'Mesa eliminada.')) setConfirmation(null)
                },
              })}
              onReorder={(table, direction) => void moveTable(table, direction)}
            />
          )}
        </div>
      )}

      {areaDialogOpen && <AreaDialog open area={editingArea} saving={model.saving} onClose={() => setAreaDialogOpen(false)} onSubmit={async (name) => {
        const success = editingArea
          ? await perform(() => model.updateArea(editingArea.id, { name }), 'Cambios guardados.')
          : await perform(() => model.createArea(name), `${name.trim()} creada.`)
        if (success) setAreaDialogOpen(false)
      }} />}

      {tableDialogOpen && <TableDialog open table={editingTable} saving={model.saving} onClose={() => setTableDialogOpen(false)} onSubmit={async (label) => {
        if (!selectedArea) return
        const success = editingTable
          ? await perform(() => model.updateTable(editingTable.id, { label }), 'Cambios guardados.')
          : await perform(() => model.createTable(selectedArea.id, label), 'Mesa creada.')
        if (success) setTableDialogOpen(false)
      }} />}

      {movingTable && <MoveTableDialog
        open
        table={movingTable}
        areas={model.areas}
        saving={model.saving}
        onClose={() => setMovingTable(null)}
        onSubmit={async (areaId) => {
          const target = model.areas.find((area) => area.id === areaId)
          const success = await perform(
            () => model.moveTable(movingTable.id, areaId),
            `${movingTable.displayName} movida a ${target?.name ?? 'otra área'}.`,
          )
          if (success) setMovingTable(null)
        }}
      />}

      {qrTable && qrArea && (
        <TableQrDialog
          open
          table={qrTable}
          area={qrArea}
          onClose={() => setQrTable(null)}
        />
      )}

      {bulkOpen && <BulkTablesDialog open tables={selectedArea?.tables ?? []} saving={model.saving} onClose={() => setBulkOpen(false)} onSubmit={async (from, to) => {
        if (!selectedArea) return
        try {
          const response = await model.bulkCreateTables(selectedArea.id, from, to)
          toast.success(response.data.skippedCount > 0
            ? `${response.data.createdCount} mesas creadas; ${response.data.skippedCount} ya existían.`
            : `${response.data.createdCount} mesas creadas.`)
          setBulkOpen(false)
        } catch (error) {
          toast.error(getApiErrorMessage(error))
        }
      }} />}

      {confirmation && (
        <ConfirmDialog open onClose={() => setConfirmation(null)} onConfirm={() => void confirmation.action()} title={confirmation.title} description={confirmation.description} confirmLabel={confirmation.label} variant={confirmation.danger ? 'danger' : 'default'} loading={model.saving} />
      )}
    </div>
  )
}
