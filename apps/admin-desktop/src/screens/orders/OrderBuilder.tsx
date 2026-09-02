import { X } from 'lucide-react'
import { useState } from 'react'
import type { Product } from '../../api/catalog'
import type { OperationTable, StaffMember } from '../../api/operations'
import { Button } from '../../components/ui/Button'
import { Dialog } from '../../components/ui/Dialog'
import { Input } from '../../components/ui/Input'
import { Select } from '../../components/ui/Select'
import { Textarea } from '../../components/ui/Textarea'
import { useCatalog } from '../../hooks/useCatalog'
import { createUlid, formatMoney, moneyMinor } from './orders.logic'

interface CartLine {
  key: string; product: Product; variantId: string; variantName: string | null
  quantity: number; optionIds: string[]; optionNames: string[]; notes: string; unitMinor: number
}
interface OrderBody {
  tableId: string; assignedWaiterId?: string; clientOperationId: string
  items: { productId: string; variantId: string; quantity: number; modifierOptionIds: string[]; notes?: string }[]
}

export function OrderBuilder({ table, staff, saving, onClose, onSubmit }: {
  table: OperationTable; staff: StaffMember[]; saving: boolean; onClose: () => void
  onSubmit: (body: OrderBody) => Promise<void>
}) {
  const catalog = useCatalog()
  const [waiterId, setWaiterId] = useState(table.session?.waiter.id ?? staff[0]?.id ?? '')
  const [categoryId, setCategoryId] = useState<string | null>(null)
  const [cart, setCart] = useState<CartLine[]>([])
  const [config, setConfig] = useState<Product | null>(null)
  const [variantId, setVariantId] = useState('')
  const [quantity, setQuantity] = useState(1)
  const [optionIds, setOptionIds] = useState<string[]>([])
  const [notes, setNotes] = useState('')
  const category = catalog.categories.find((item) => item.id === categoryId) ?? catalog.categories[0]
  const total = cart.reduce((sum, line) => sum + line.unitMinor * line.quantity, 0)

  async function configure(product: Product) {
    const detail = (await catalog.fetchProduct(product.id)).data.product
    const variant = detail.variants.find((item) => item.isDefault) ?? detail.variants[0]
    setConfig(detail); setVariantId(variant?.id ?? ''); setQuantity(1); setOptionIds([]); setNotes('')
  }
  function addLine() {
    if (!config) return
    const variant = config.variants.find((item) => item.id === variantId)
    if (!variant) return
    const assignments = (config.modifierAssignments ?? []).filter((item) => item.isActive)
    const invalid = assignments.some((assignment) => {
      const count = assignment.group.options.filter((option) => optionIds.includes(option.id)).length
      return count < assignment.minSelect || count > assignment.maxSelect
    })
    if (invalid) return
    const selected = assignments.flatMap((assignment) => assignment.group.options).filter((option) => optionIds.includes(option.id))
    setCart((current) => [...current, {
      key: createUlid(), product: config, variantId, variantName: variant.name, quantity, optionIds,
      optionNames: selected.map((item) => item.name), notes,
      unitMinor: moneyMinor(variant.price) + selected.reduce((sum, item) => sum + moneyMinor(item.priceDelta), 0),
    }])
    setConfig(null)
  }
  const submit = () => onSubmit({
    tableId: table.id, assignedWaiterId: table.session ? undefined : waiterId, clientOperationId: createUlid(),
    items: cart.map((line) => ({ productId: line.product.id, variantId: line.variantId, quantity: line.quantity, modifierOptionIds: line.optionIds, notes: line.notes || undefined })),
  })
  return <Dialog open onClose={onClose} size="lg" title={config ? config.name : `Nuevo pedido · ${table.label}`}
    description={config ? 'Configura la presentación y sus opciones.' : 'La mesa se abre únicamente al confirmar.'}
    dismissible={!saving} footer={config
      ? <><Button variant="outline" onClick={() => setConfig(null)}>Volver</Button><Button onClick={addLine}>Agregar al pedido</Button></>
      : <><Button variant="outline" onClick={onClose}>Cancelar</Button><Button loading={saving} disabled={!cart.length || !waiterId} onClick={() => void submit()}>Confirmar · {formatMoney(total)}</Button></>}>
    {config ? <div className="order-config">
      <Select label="Presentación" value={variantId} onChange={(event) => setVariantId(event.target.value)}
        options={config.variants.filter((item) => item.isActive && item.isAvailable).map((item) => ({ value: item.id, label: `${item.name ?? 'Única'} · ${formatMoney(moneyMinor(item.price))}` }))} />
      <Input label="Cantidad" type="number" min={1} max={100} value={quantity}
        onChange={(event) => setQuantity(Math.max(1, Math.min(100, Number(event.target.value))))} />
      {(config.modifierAssignments ?? []).filter((item) => item.isActive).map((assignment) =>
        <fieldset key={assignment.id}><legend>{assignment.group.name} <small>{assignment.minSelect ? `Elige ${assignment.minSelect}–${assignment.maxSelect}` : `Hasta ${assignment.maxSelect}`}</small></legend>
          {assignment.group.options.filter((item) => item.isActive).map((option) =>
            <label key={option.id}><input type="checkbox" checked={optionIds.includes(option.id)}
              onChange={() => setOptionIds((current) => current.includes(option.id) ? current.filter((id) => id !== option.id) : [...current, option.id])} />
              <span>{option.name}</span><small>+ {formatMoney(moneyMinor(option.priceDelta))}</small></label>)}
        </fieldset>)}
      {config.allowNotes && <Textarea label="Observaciones" maxLength={1000} value={notes} onChange={(event) => setNotes(event.target.value)} />}
    </div> : <div className="builder-layout"><aside>
      <Select label="Mesero responsable" disabled={Boolean(table.session)} value={waiterId}
        onChange={(event) => setWaiterId(event.target.value)} options={staff.map((person) => ({ value: person.id, label: person.name }))} placeholder="Selecciona un mesero" />
      <h3>Categorías</h3>{catalog.categories.map((item) =>
        <button type="button" className={item.id === category?.id ? 'builder-category builder-category--active' : 'builder-category'}
          key={item.id} onClick={() => setCategoryId(item.id)}>{item.name}</button>)}
    </aside><section><div className="builder-products">
      {category?.products.filter((item) => item.isActive && item.isAvailable).map((product) =>
        <button type="button" key={product.id} onClick={() => void configure(product)}>
          <strong>{product.name}</strong><span>{formatMoney(moneyMinor(product.basePrice ?? '0'))}</span>
        </button>)}
    </div><div className="cart"><h3>Pedido</h3>{cart.length === 0 ? <p>Toca un producto para agregarlo.</p> : cart.map((line) =>
      <div key={line.key} className="cart-line"><div><strong>{line.quantity} × {line.product.name}</strong>
        <small>{[line.variantName, ...line.optionNames, line.notes].filter(Boolean).join(' · ')}</small></div>
        <span>{formatMoney(line.unitMinor * line.quantity)}</span>
        <button type="button" aria-label="Quitar producto" onClick={() => setCart((current) => current.filter((item) => item.key !== line.key))}><X size={16} /></button>
      </div>)}</div></section></div>}
  </Dialog>
}
