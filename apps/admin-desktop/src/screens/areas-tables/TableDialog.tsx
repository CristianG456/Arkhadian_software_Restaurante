import { useState } from 'react'
import type { FormEvent } from 'react'
import type { RestaurantTable } from '../../api/areas'
import { Button } from '../../components/ui/Button'
import { Dialog } from '../../components/ui/Dialog'
import { Input } from '../../components/ui/Input'

interface TableDialogProps {
  open: boolean
  table?: RestaurantTable | null
  saving: boolean
  onClose: () => void
  onSubmit: (label: string) => Promise<void>
}

export function TableDialog(props: TableDialogProps) {
  const { open, table, saving, onClose, onSubmit } = props
  const [label, setLabel] = useState(table?.label ?? '')
  const [error, setError] = useState('')

  async function submit(event?: FormEvent) {
    event?.preventDefault()
    if (!label.trim()) {
      setError('Escribe el número o nombre de la mesa.')
      return
    }
    await onSubmit(label)
  }

  return (
    <Dialog
      open={open}
      onClose={onClose}
      title={table ? 'Editar mesa' : 'Agregar mesa'}
      size='sm'
      dismissible={!saving}
      description='Escribe solo el identificador; la aplicación mostrará el prefijo Mesa.'
      footer={
        <>
          <Button variant='outline' onClick={onClose} disabled={saving}>Cancelar</Button>
          <Button onClick={() => void submit()} loading={saving}>
            {table ? 'Guardar cambios' : 'Agregar mesa'}
          </Button>
        </>
      }
    >
      <form className='areas-tables-form' onSubmit={(event) => void submit(event)}>
        <Input
          label='Número / nombre'
          value={label}
          onChange={(event) => setLabel(event.target.value)}
          error={error}
          required
          autoFocus
          maxLength={50}
          placeholder='Ej. 4 o A1'
        />
      </form>
    </Dialog>
  )
}
