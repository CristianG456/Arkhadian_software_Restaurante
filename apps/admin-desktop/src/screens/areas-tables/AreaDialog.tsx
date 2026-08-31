import { useState } from 'react'
import type { FormEvent } from 'react'
import type { Area } from '../../api/areas'
import { Button } from '../../components/ui/Button'
import { Dialog } from '../../components/ui/Dialog'
import { Input } from '../../components/ui/Input'

interface AreaDialogProps {
  open: boolean
  area?: Area | null
  saving: boolean
  onClose: () => void
  onSubmit: (name: string) => Promise<void>
}

export function AreaDialog({ open, area, saving, onClose, onSubmit }: AreaDialogProps) {
  const [name, setName] = useState(area?.name ?? '')
  const [error, setError] = useState('')

  async function submit(event?: FormEvent) {
    event?.preventDefault()
    if (!name.trim()) {
      setError('Escribe el nombre del área.')
      return
    }
    await onSubmit(name)
  }

  return (
    <Dialog
      open={open}
      onClose={onClose}
      title={area ? 'Editar área' : 'Nueva área'}
      description='Organiza las mesas por espacios físicos del restaurante.'
      size='sm'
      dismissible={!saving}
      footer={
        <>
          <Button variant='outline' onClick={onClose} disabled={saving}>Cancelar</Button>
          <Button onClick={() => void submit()} loading={saving}>
            {area ? 'Guardar cambios' : 'Crear área'}
          </Button>
        </>
      }
    >
      <form onSubmit={(event) => void submit(event)}>
        <Input
          label='Nombre'
          value={name}
          onChange={(event) => setName(event.target.value)}
          error={error}
          required
          autoFocus
          maxLength={100}
          placeholder='Ej. Sala VIP'
        />
      </form>
    </Dialog>
  )
}
