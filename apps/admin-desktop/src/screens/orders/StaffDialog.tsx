import { useState } from 'react'
import type { StaffMember } from '../../api/operations'
import { Button } from '../../components/ui/Button'
import { Dialog } from '../../components/ui/Dialog'
import { Input } from '../../components/ui/Input'
import { useToast } from '../../components/ui/ToastProvider'
import { operationError } from '../../hooks/useOrdersOperation'

export function StaffDialog({ staff, saving, onClose, create, update }: {
  staff: StaffMember[]; saving: boolean; onClose: () => void
  create: (name: string) => Promise<unknown>
  update: (id: string, body: { name?: string; isActive?: boolean }) => Promise<unknown>
}) {
  const toast = useToast(); const [name, setName] = useState('')
  async function run(action: () => Promise<unknown>, message: string) {
    try { await action(); toast.success(message); setName('') } catch (error) { toast.error(operationError(error)) }
  }
  return <Dialog open onClose={onClose} title="Meseros" description="Personal asignable a la operación de mesas." dismissible={!saving}
    footer={<Button variant="outline" onClick={onClose}>Cerrar</Button>}>
    <form className="staff-create" onSubmit={(event) => { event.preventDefault(); if (name.trim()) void run(() => create(name), 'Mesero creado.') }}>
      <Input label="Nombre" value={name} onChange={(event) => setName(event.target.value)} maxLength={100} />
      <Button type="submit" loading={saving}>Agregar</Button>
    </form>
    <div className="staff-list">{staff.map((person) => <div key={person.id}><span><strong>{person.name}</strong><small>{person.isActive ? 'Activo' : 'Inactivo'}</small></span>
      <Button size="sm" variant="outline" disabled={saving} onClick={() => void run(() => update(person.id, { isActive: !person.isActive }), person.isActive ? 'Mesero desactivado.' : 'Mesero activado.')}>{person.isActive ? 'Desactivar' : 'Activar'}</Button>
    </div>)}</div>
  </Dialog>
}
