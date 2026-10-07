import { Plus, RefreshCw, UserRound, UsersRound } from 'lucide-react'
import { useState } from 'react'
import type { OperationTable, TableSession } from '../../api/operations'
import { isInfrastructureError } from '../../api/ApiClientError'
import { useSystemStatusContext } from '../../app/SystemStatusContext'
import { Alert } from '../../components/ui/Alert'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { LoadingState } from '../../components/ui/Spinner'
import { useToast } from '../../components/ui/ToastProvider'
import { operationError, useOrdersOperation } from '../../hooks/useOrdersOperation'
import { OrderBuilder } from './OrderBuilder'
import { SessionDialog } from './SessionDialog'
import { StaffDialog } from './StaffDialog'
import { formatMoney, moneyMinor } from './orders.logic'
import './OrdersScreen.css'

export function OrdersScreen() {
  const model = useOrdersOperation(); const toast = useToast()
  const system = useSystemStatusContext(); const available = system.snapshot.localOperationAvailable
  const [areaId, setAreaId] = useState<string | null>(null)
  const [builderTable, setBuilderTable] = useState<OperationTable | null>(null)
  const [session, setSession] = useState<TableSession | null>(null)
  const [staffOpen, setStaffOpen] = useState(false)
  const area = model.areas.find((item) => item.id === areaId) ?? model.areas[0] ?? null
  async function openTable(table: OperationTable) {
    if (!available || model.error) return
    if (!table.session) { setBuilderTable(table); return }
    try { setSession((await model.fetchSession(table.session.id)).data.session) } catch (error) { if (!isInfrastructureError(error)) toast.error(operationError(error)) }
  }
  async function refreshSession() { if (session) setSession((await model.fetchSession(session.id)).data.session) }
  if (model.loading) return <LoadingState label="Cargando operación de mesas..." />
  return <div className="orders-screen">
    <header className="orders-header"><div><h1>Pedidos</h1><p>Abre pedidos, consulta consumos y administra la atención de cada mesa.</p></div>
      <div><Button variant="outline" icon={<UsersRound size={18} />} onClick={() => setStaffOpen(true)}>Meseros</Button>
        <Button variant="outline" icon={<RefreshCw size={18} />} onClick={() => void model.refresh()}>Actualizar</Button></div>
    </header>
    {model.error && <Alert variant="danger" title="Operación local no disponible"><span>{model.error}</span><Button size="sm" variant="outline" onClick={() => void model.refresh()}>Reintentar</Button></Alert>}
    <div className="orders-layout"><aside className="orders-areas"><h2>Áreas</h2>{model.areas.map((item) =>
      <button key={item.id} type="button" className={item.id === area?.id ? 'orders-area orders-area--active' : 'orders-area'} onClick={() => setAreaId(item.id)}>
        <strong>{item.name}</strong><small>{item.tables.filter((table) => table.state === 'OCCUPIED').length} ocupadas · {item.tables.length} mesas</small>
      </button>)}</aside>
      <section className="operation"><div className="operation__heading"><div><h2>{area?.name ?? 'Mesas'}</h2><p>Toca una mesa libre para tomar un pedido o una ocupada para ver el consumo.</p></div>
        <div className="operation__legend"><Badge variant="success">Libre</Badge><Badge variant="warning">Ocupada</Badge></div></div>
        <div className="operation-grid">{area?.tables.map((table) =>
          <button type="button" key={table.id} className={`operation-table operation-table--${table.state.toLowerCase()}`} onClick={() => void openTable(table)}>
            <span className="operation-table__top"><strong>{table.label}</strong><Badge variant={table.state === 'FREE' ? 'success' : 'warning'}>{table.state === 'FREE' ? 'Libre' : 'Ocupada'}</Badge></span>
            {table.session ? <><span><UserRound size={15} /> {table.session.waiter.name}</span><span>{table.session.ordersCount} pedidos · <strong>{formatMoney(moneyMinor(table.session.total))}</strong></span></>
              : <span className="operation-table__free"><Plus size={16} /> Tomar pedido</span>}
          </button>)}</div>
      </section>
    </div>
    {builderTable && <OrderBuilder table={builderTable} staff={model.staff.filter((person) => person.isActive)} saving={model.saving} onClose={() => setBuilderTable(null)}
      onSubmit={async (body) => { try { await model.createOrder(body); toast.success('Pedido confirmado.'); setBuilderTable(null); if (session) await refreshSession() } catch (error) { toast.error(operationError(error)) } }} />}
    {session && <SessionDialog session={session} areas={model.areas} staff={model.staff.filter((person) => person.isActive)} saving={model.saving}
      onClose={() => setSession(null)} onAdd={() => setBuilderTable({ id: session.table.id, label: session.table.label, state: 'OCCUPIED', session: { id: session.id, waiter: session.waiter, openedAt: session.openedAt, ordersCount: session.orders.length, total: session.total } })}
      onRefresh={refreshSession} transfer={model.transferWaiter} move={model.moveSession} cancelOrder={model.cancelOrder} cancelItem={model.cancelItem} />}
    {staffOpen && <StaffDialog staff={model.staff} saving={model.saving} onClose={() => setStaffOpen(false)} create={model.createStaff} update={model.updateStaff} />}
  </div>
}
