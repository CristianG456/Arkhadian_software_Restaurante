import { FileText, Plus, Printer, RotateCcw } from 'lucide-react'
import { useState } from 'react'
import type { Preaccount } from '../../api/production'
import { previewPreaccount, printPreaccount, reprintOrder, requestBill } from '../../api/production'
import type { OperationArea, StaffMember, TableSession } from '../../api/operations'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Dialog } from '../../components/ui/Dialog'
import { Select } from '../../components/ui/Select'
import { useToast } from '../../components/ui/ToastProvider'
import { operationError } from '../../hooks/useOrdersOperation'
import { formatMoney, moneyMinor } from './orders.logic'

export function SessionDialog({ session, areas, staff, saving, onClose, onAdd, onRefresh, transfer, move, cancelOrder, cancelItem }: {
  session: TableSession; areas: OperationArea[]; staff: StaffMember[]; saving: boolean
  onClose: () => void; onAdd: () => void; onRefresh: () => Promise<void>
  transfer: (id: string, waiter: string, reason?: string) => Promise<unknown>
  move: (id: string, table: string, reason?: string) => Promise<unknown>
  cancelOrder: (id: string, reason: string) => Promise<unknown>
  cancelItem: (id: string, reason: string) => Promise<unknown>
}) {
  const toast = useToast()
  const [preaccount, setPreaccount] = useState<Preaccount | null>(null)
  const [preaccountLoading, setPreaccountLoading] = useState(false)
  const freeTables = areas.flatMap((area) => area.tables.map((table) => ({ ...table, areaName: area.name }))).filter((table) => table.state === 'FREE')
  async function run(action: () => Promise<unknown>, message: string) {
    try { await action(); await onRefresh(); toast.success(message) } catch (error) { toast.error(operationError(error)) }
  }
  async function openPreaccount() {
    setPreaccountLoading(true)
    try { const response = await previewPreaccount(session.id); setPreaccount(response.data.preaccount) } catch (error) { toast.error(operationError(error)) } finally { setPreaccountLoading(false) }
  }
  return <Dialog open onClose={onClose} size="lg" title={`${session.table.label} · ${session.waiter.name}`}
    description={`${session.table.areaName} · abierta ${new Date(session.openedAt).toLocaleString('es-CO')}`} dismissible={!saving}
    footer={<><Select aria-label="Trasladar a otra mesa" placeholder="Trasladar mesa..." value="" disabled={!freeTables.length || saving}
      options={freeTables.map((table) => ({ value: table.id, label: `${table.label} · ${table.areaName}` }))}
      onChange={(event) => void run(() => move(session.id, event.target.value, 'Traslado desde operación'), 'Mesa trasladada.')} />
      <Select aria-label="Cambiar mesero" placeholder="Cambiar mesero..." value="" disabled={staff.length < 2 || saving}
        options={staff.filter((person) => person.id !== session.waiter.id).map((person) => ({ value: person.id, label: person.name }))}
        onChange={(event) => void run(() => transfer(session.id, event.target.value, 'Cambio desde operación'), 'Mesero actualizado.')} />
      <Button icon={<Plus size={16} />} onClick={onAdd}>Agregar pedido</Button></>}>
    <div className="session-total"><span>Total activo</span><strong>{formatMoney(moneyMinor(session.total))}</strong></div>
    <div className='session-bill-actions'><Button variant='outline' icon={<FileText size={16} />} loading={preaccountLoading} onClick={() => void openPreaccount()}>Ver precuenta</Button><Button variant='outline' disabled={Boolean(session.billRequestedAt)} onClick={() => void run(() => requestBill(session.id), 'Solicitud registrada; la mesa continúa abierta.')}>{session.billRequestedAt ? 'Cuenta solicitada' : 'Solicitar cuenta'}</Button></div>
    {session.orders.length === 0 ? <p>No hay pedidos confirmados.</p> : session.orders.map((order) =>
      <article className={order.status === 'CANCELLED' ? 'session-order session-order--cancelled' : 'session-order'} key={order.id}>
        <header><div><strong>Pedido #{order.orderNumber}</strong><small>{new Date(order.createdAt).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' })} · {order.waiter.name}</small></div>
          {order.status === 'CONFIRMED' && <Button size="sm" variant="ghost" onClick={() => { const reason = window.prompt('Motivo de cancelación'); if (reason) void run(() => cancelOrder(order.id, reason), 'Pedido cancelado.') }}>Cancelar pedido</Button>}
        </header>
        <div className='session-order__printing'><Button size='sm' variant='outline' icon={<RotateCcw size={14} />} onClick={() => void run(() => reprintOrder(order.id), 'Reimpresión agregada a la cola.')}>Reimprimir comanda</Button>{order.printJobs?.map((job) => <Badge key={job.id} variant={job.status === 'PRINTED' ? 'success' : job.status === 'FAILED' ? 'danger' : 'warning'}>{job.center?.name ?? 'Sin centro'}: {job.status}</Badge>)}</div>
        {order.items.map((item) => <div className={item.status === 'CANCELLED' ? 'session-item session-item--cancelled' : 'session-item'} key={item.id}>
          <div><strong>{item.quantity} × {item.productName}</strong><small>{[item.variantName, ...item.modifiers.map((modifier) => modifier.optionName), item.notes].filter(Boolean).join(' · ')}</small></div>
          <span>{formatMoney(moneyMinor(item.lineTotal))}</span>
          {item.status === 'ACTIVE' && order.status === 'CONFIRMED' && <button type="button" onClick={() => { const reason = window.prompt('Motivo de cancelación'); if (reason) void run(() => cancelItem(item.id, reason), 'Producto cancelado.') }}>Cancelar</button>}
        </div>)}
      </article>)}
    <Dialog open={Boolean(preaccount)} onClose={() => setPreaccount(null)} title='PRECUENTA' description='Vista previa · NO ES FACTURA' footer={<><Button variant='outline' onClick={() => setPreaccount(null)}>Cerrar</Button><Button icon={<Printer size={16} />} onClick={() => void run(() => printPreaccount(session.id), 'Precuenta agregada a impresión.')}>Imprimir</Button></>}>{preaccount && <div className='preaccount-preview'><strong>{preaccount.restaurantName}</strong><span>{preaccount.location} · {preaccount.waiter}</span>{preaccount.items.map((item, index) => <div key={`${item.name}-${index}`}><span>{item.quantity} × {item.name}</span><strong>{formatMoney(moneyMinor(item.lineTotal))}</strong></div>)}<div><span>Total</span><strong>{formatMoney(moneyMinor(preaccount.total))}</strong></div><b>{preaccount.notice}</b></div>}</Dialog>
  </Dialog>
}
