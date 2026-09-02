import { Pencil, Power, RotateCcw, Trash2 } from 'lucide-react'
import { useState } from 'react'
import type { ModifierGroup, ModifierOption } from '../../api/catalog'
import { ActionMenu } from '../../components/ui/ActionMenu'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { ConfirmDialog } from '../../components/ui/ConfirmDialog'
import { Dialog } from '../../components/ui/Dialog'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { NameDialog } from './CatalogDialogs'
import { formatCop, normalizeCatalogPrice } from './catalog.logic'
import './ModifierLibraryDialog.css'

interface Props {
  groups: ModifierGroup[]
  saving: boolean
  onClose: () => void
  onCreateGroup: (name: string) => Promise<void>
  onUpdateGroup: (id: string, changes: { name?: string; isActive?: boolean }) => Promise<void>
  onDeleteGroup: (id: string) => Promise<void>
  onCreateOption: (groupId: string, name: string, price: string) => Promise<void>
  onUpdateOption: (groupId: string, id: string, changes: { name?: string; priceDelta?: string; isActive?: boolean }) => Promise<void>
  onDeleteOption: (groupId: string, id: string) => Promise<void>
}

interface Confirmation {
  title: string
  description: string
  action: () => Promise<void>
}

export function ModifierLibraryDialog(props: Props) {
  const [groupName, setGroupName] = useState('')
  const [selectedId, setSelectedId] = useState(props.groups[0]?.id ?? '')
  const [optionName, setOptionName] = useState('')
  const [optionPrice, setOptionPrice] = useState('0')
  const [editingGroup, setEditingGroup] = useState<ModifierGroup | null>(null)
  const [editingOption, setEditingOption] = useState<{ group: ModifierGroup; option: ModifierOption } | null>(null)
  const [confirmation, setConfirmation] = useState<Confirmation | null>(null)
  const selected = props.groups.find((group) => group.id === selectedId) ?? props.groups[0] ?? null

  return <>
    <Dialog open title='Biblioteca de opciones' description='Los grupos son reutilizables en varios productos.' size='lg' onClose={props.onClose} dismissible={!props.saving} footer={<Button variant='outline' onClick={props.onClose}>Cerrar</Button>}>
      <div className='catalog-tab'>
        <div className='catalog-inline'>
          <Input label='Nuevo grupo' value={groupName} onChange={(event) => setGroupName(event.target.value)} placeholder='Ej. Término de carne' />
          <span />
          <Button disabled={props.saving || !groupName.trim()} onClick={async () => { await props.onCreateGroup(groupName); setGroupName('') }}>Crear grupo</Button>
        </div>

        <div className='modifier-groups'>
          {props.groups.map((group) => <section key={group.id} className={group.id === selected?.id ? 'modifier-group modifier-group--selected' : 'modifier-group'}>
            <button type='button' className='modifier-group__select' onClick={() => setSelectedId(group.id)}>
              <span><strong>{group.name}</strong><small>{group.options.length} opciones · usado en {group.assignmentsCount} {group.assignmentsCount === 1 ? 'producto' : 'productos'}</small></span>
              <Badge variant={group.isActive ? 'success' : 'neutral'}>{group.isActive ? 'Activo' : 'Inactivo'}</Badge>
            </button>
            <ActionMenu triggerLabel={`Acciones de ${group.name}`} items={[
              { label: 'Editar', icon: <Pencil size={16} />, onSelect: () => setEditingGroup(group) },
              { label: group.isActive ? 'Desactivar' : 'Reactivar', icon: group.isActive ? <Power size={16} /> : <RotateCcw size={16} />, onSelect: () => void props.onUpdateGroup(group.id, { isActive: !group.isActive }) },
              { label: 'Eliminar', icon: <Trash2 size={16} />, danger: true, onSelect: () => setConfirmation({ title: 'Eliminar grupo', description: `¿Seguro que deseas eliminar “${group.name}”?${group.options.length > 0 ? ' También se eliminarán las opciones pertenecientes a este grupo.' : ''}`, action: () => props.onDeleteGroup(group.id) }) },
            ]} />
          </section>)}
        </div>

        {selected && <section className='modifier-options'>
          <div className='modifier-options__heading'><div><h3>{selected.name}</h3><p>Opciones del grupo</p></div></div>
          {selected.options.length === 0 ? <p className='modifier-options__empty'>Este grupo todavía no tiene opciones.</p> : <div className='catalog-list'>
            {selected.options.map((option) => <div className='catalog-row' key={option.id}>
              <span><strong>{option.name}</strong><small>{formatCop(option.priceDelta)} de precio adicional · {option.isActive ? 'Activa' : 'Inactiva'}</small></span>
              <ActionMenu triggerLabel={`Acciones de ${option.name}`} items={[
                { label: 'Editar', icon: <Pencil size={16} />, onSelect: () => setEditingOption({ group: selected, option }) },
                { label: option.isActive ? 'Desactivar' : 'Reactivar', icon: option.isActive ? <Power size={16} /> : <RotateCcw size={16} />, onSelect: () => void props.onUpdateOption(selected.id, option.id, { isActive: !option.isActive }) },
                { label: 'Eliminar', icon: <Trash2 size={16} />, danger: true, onSelect: () => setConfirmation({ title: 'Eliminar opción', description: `¿Seguro que deseas eliminar “${option.name}”?`, action: () => props.onDeleteOption(selected.id, option.id) }) },
              ]} />
            </div>)}
          </div>}
          <Select label='Agregar opción al grupo' value={selected.id} onChange={(event) => setSelectedId(event.target.value)} options={props.groups.filter((group) => group.isActive).map((group) => ({ value: group.id, label: group.name }))} />
          <div className='catalog-inline'>
            <Input label='Nombre de opción' value={optionName} onChange={(event) => setOptionName(event.target.value)} placeholder='Ej. Bien asada' />
            <Input label='Precio adicional (opcional)' description='Déjalo en $0 si esta opción no cambia el precio.' value={optionPrice} onChange={(event) => setOptionPrice(event.target.value)} inputMode='numeric' />
            <Button disabled={props.saving || !selected.isActive || !optionName.trim()} onClick={async () => { await props.onCreateOption(selected.id, optionName, normalizeCatalogPrice(optionPrice)); setOptionName(''); setOptionPrice('0') }}>Agregar opción</Button>
          </div>
        </section>}
      </div>
    </Dialog>

    {editingGroup && <NameDialog open title='Editar grupo' label='Nombre' initial={editingGroup.name} saving={props.saving} onClose={() => setEditingGroup(null)} onSubmit={async (name) => { await props.onUpdateGroup(editingGroup.id, { name }); setEditingGroup(null) }} />}
    {editingOption && <OptionEditDialog editing={editingOption} saving={props.saving} onClose={() => setEditingOption(null)} onSubmit={async (name, priceDelta) => { await props.onUpdateOption(editingOption.group.id, editingOption.option.id, { name, priceDelta }); setEditingOption(null) }} />}
    {confirmation && <ConfirmDialog open title={confirmation.title} description={confirmation.description} confirmLabel='Eliminar' variant='danger' loading={props.saving} onClose={() => setConfirmation(null)} onConfirm={() => void (async () => { await confirmation.action(); setConfirmation(null) })()} />}
  </>
}

function OptionEditDialog({ editing, saving, onClose, onSubmit }: { editing: { option: ModifierOption }; saving: boolean; onClose: () => void; onSubmit: (name: string, priceDelta: string) => Promise<void> }) {
  const [name, setName] = useState(editing.option.name)
  const [price, setPrice] = useState(editing.option.priceDelta)
  return <Dialog open title='Editar opción' onClose={onClose} dismissible={!saving} footer={<><Button variant='outline' onClick={onClose}>Cancelar</Button><Button disabled={saving || !name.trim()} onClick={() => void onSubmit(name, normalizeCatalogPrice(price))}>Guardar cambios</Button></>}><div className='catalog-tab'><Input label='Nombre' value={name} onChange={(event) => setName(event.target.value)} /><Input label='Precio adicional (opcional)' description='Déjalo en $0 si esta opción no cambia el precio.' value={price} onChange={(event) => setPrice(event.target.value)} inputMode='decimal' /></div></Dialog>
}
