import { useState } from 'react'
import type { ReactNode } from 'react'
import { Download, Pencil, Plus, Search, Trash2 } from 'lucide-react'
import { Button } from '../../components/ui/Button'
import { IconButton } from '../../components/ui/IconButton'
import { Badge } from '../../components/ui/Badge'
import type { BadgeVariant } from '../../components/ui/Badge'
import { Input } from '../../components/ui/Input'
import { SearchInput } from '../../components/ui/SearchInput'
import { Textarea } from '../../components/ui/Textarea'
import { Select } from '../../components/ui/Select'
import { Checkbox } from '../../components/ui/Checkbox'
import { Switch } from '../../components/ui/Switch'
import { RadioGroup } from '../../components/ui/RadioGroup'
import { Tabs } from '../../components/ui/Tabs'
import type { TabItem } from '../../components/ui/Tabs'
import { Panel } from '../../components/ui/Panel'
import { Alert } from '../../components/ui/Alert'
import { Dialog } from '../../components/ui/Dialog'
import { ConfirmDialog } from '../../components/ui/ConfirmDialog'
import { useToast } from '../../components/ui/ToastProvider'
import { EmptyState } from '../../components/ui/EmptyState'
import { LoadingState } from '../../components/ui/Spinner'
import { Skeleton } from '../../components/ui/Skeleton'
import { NumericKeypad } from '../../components/ui/NumericKeypad'
import { ActionMenu } from '../../components/ui/ActionMenu'
import { DataList } from '../../components/ui/DataList'
import './ComponentShowcaseScreen.css'

const BADGE_EXAMPLES: Array<{ variant: BadgeVariant; text: string }> = [
  { variant: 'success', text: 'Correcto' },
  { variant: 'danger', text: 'Error' },
  { variant: 'warning', text: 'Advertencia' },
  { variant: 'info', text: 'Información' },
  { variant: 'neutral', text: 'Sin estado' },
  { variant: 'checking', text: 'Comprobando' },
]

const TAB_ITEMS: TabItem[] = [
  { value: 'general', label: 'General' },
  { value: 'detalles', label: 'Detalles' },
  { value: 'avanzado', label: 'Avanzado (no disponible)', disabled: true },
]

const TAB_CONTENT: Record<string, string> = {
  general: 'Contenido de la pestaña General.',
  detalles: 'Contenido de la pestaña Detalles.',
}

interface DemoRow {
  id: string
  name: string
  status: 'Activo' | 'Pendiente'
}

const DEMO_ROWS: DemoRow[] = [
  { id: '1', name: 'Elemento 1', status: 'Activo' },
  { id: '2', name: 'Elemento 2', status: 'Pendiente' },
]

const INDEX_ITEMS = [
  { id: 'botones', label: 'Botones' },
  { id: 'campos', label: 'Campos' },
  { id: 'opciones', label: 'Opciones' },
  { id: 'pestanas', label: 'Pestañas' },
  { id: 'estados', label: 'Estados' },
  { id: 'avisos', label: 'Avisos' },
  { id: 'ventanas', label: 'Ventanas' },
  { id: 'carga', label: 'Carga' },
  { id: 'teclado', label: 'Teclado' },
  { id: 'menu', label: 'Menú' },
  { id: 'listados', label: 'Listados' },
]

function ShowcaseSection({
  id,
  title,
  description,
  children,
}: {
  id: string
  title: string
  description: string
  children: ReactNode
}) {
  return (
    <section id={id} className="showcase__section">
      <Panel title={title} description={description}>
        {children}
      </Panel>
    </section>
  )
}

function SubHeading({ title, description }: { title: string; description?: string }) {
  return (
    <div className="showcase__subheading">
      <h3>{title}</h3>
      {description && <p>{description}</p>}
    </div>
  )
}

function Kicker({ children }: { children: ReactNode }) {
  return <span className="showcase__kicker">{children}</span>
}

/**
 * Development-only visual reference for every Phase 2 UI primitive,
 * written for anyone reviewing Restaurant Admin - not just developers.
 * Never shipped: only reachable via AppShell's dev-only trigger, which
 * only renders when import.meta.env.DEV is true, and this module is
 * loaded via a dynamic import so it never lands in a production bundle.
 */
export function ComponentShowcaseScreen() {
  const toast = useToast()

  const [search, setSearch] = useState('')
  const [textValue, setTextValue] = useState('')
  const [selectValue, setSelectValue] = useState('')
  const [checked, setChecked] = useState(true)
  const [switchOn, setSwitchOn] = useState(false)
  const [radioValue, setRadioValue] = useState('opcion-1')
  const [tab, setTab] = useState('general')
  const [dialogOpen, setDialogOpen] = useState(false)
  const [confirmOpen, setConfirmOpen] = useState(false)
  const [keypadValue, setKeypadValue] = useState('')

  function scrollToSection(id: string) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }

  return (
    <div className="showcase">
      <div className="showcase__intro">
        <h1>Pruebas de interfaz</h1>
        <p>Referencia visual de los controles y elementos que utilizará Restaurant Admin.</p>
        <p className="showcase__intro-note">Esta sección solo está disponible durante el desarrollo.</p>
      </div>

      <nav className="showcase__index" aria-label="Categorías">
        {INDEX_ITEMS.map((item) => (
          <button
            key={item.id}
            type="button"
            className="showcase__index-item"
            onClick={() => scrollToSection(item.id)}
          >
            {item.label}
          </button>
        ))}
      </nav>

      <ShowcaseSection
        id="botones"
        title="Botones y acciones"
        description="Se utilizan para ejecutar las acciones principales y secundarias del sistema."
      >
        <div className="showcase__row">
          <Button variant="primary">Principal</Button>
          <Button variant="secondary">Secundario</Button>
          <Button variant="outline">Contorno</Button>
          <Button variant="ghost">Discreto</Button>
          <Button variant="danger">Peligro</Button>
        </div>
        <div className="showcase__row">
          <Button icon={<Plus size={16} />}>Con icono</Button>
          <Button loading>Cargando</Button>
          <Button disabled>Deshabilitado</Button>
          <Button size="sm">Pequeño</Button>
          <Button size="lg">Grande</Button>
        </div>

        <SubHeading title="Botones de icono" description="Acciones rápidas representadas con un icono." />
        <div className="showcase__row">
          <IconButton icon={<Pencil size={18} />} label="Editar" variant="ghost" />
          <IconButton icon={<Trash2 size={18} />} label="Eliminar" variant="danger" />
          <IconButton icon={<Download size={18} />} label="Descargar" variant="outline" />
          <IconButton icon={<Plus size={18} />} label="Cargando" variant="secondary" loading />
        </div>
      </ShowcaseSection>

      <ShowcaseSection
        id="campos"
        title="Campos y búsqueda"
        description="Controles para escribir, buscar y seleccionar información."
      >
        <div className="showcase__grid">
          <Input label="Nombre" placeholder="Escribe un nombre" />
          <Input label="Buscar" icon={<Search size={16} />} placeholder="Buscar..." />
          <Input label="Campo deshabilitado" disabled value="No editable" readOnly />
          <Input label="Campo con error" error="Este campo es obligatorio." />
          <div>
            <Kicker>Lista de opciones</Kicker>
            <Select
              label="Selecciona una opción"
              placeholder="Elige..."
              value={selectValue}
              onChange={(event) => setSelectValue(event.target.value)}
              options={[
                { value: '1', label: 'Opción 1' },
                { value: '2', label: 'Opción 2' },
                { value: '3', label: 'Opción 3 (no disponible)', disabled: true },
              ]}
            />
          </div>
          <div>
            <Kicker>Búsqueda</Kicker>
            <SearchInput value={search} onChange={setSearch} />
          </div>
        </div>
        <Textarea
          label="Observaciones"
          placeholder="Escribe una observación..."
          value={textValue}
          onChange={(event) => setTextValue(event.target.value)}
        />
      </ShowcaseSection>

      <ShowcaseSection
        id="opciones"
        title="Selección de opciones"
        description="Controles para marcar, activar o elegir entre varias opciones."
      >
        <Kicker>Casilla</Kicker>
        <Checkbox
          label="Acepto los términos"
          checked={checked}
          onChange={(event) => setChecked(event.target.checked)}
        />

        <Kicker>Interruptor</Kicker>
        <p className="showcase__hint">Permite activar o desactivar una opción.</p>
        <Switch
          label="Notificaciones"
          description="Activar alertas del sistema"
          checked={switchOn}
          onChange={(event) => setSwitchOn(event.target.checked)}
        />

        <Kicker>Selección única</Kicker>
        <p className="showcase__hint">Permite elegir una sola opción entre varias.</p>
        <RadioGroup
          label="Preferencia"
          value={radioValue}
          onChange={setRadioValue}
          options={[
            { value: 'opcion-1', label: 'Opción 1' },
            { value: 'opcion-2', label: 'Opción 2' },
            { value: 'opcion-3', label: 'Opción 3', disabled: true },
          ]}
        />
      </ShowcaseSection>

      <ShowcaseSection
        id="pestanas"
        title="Pestañas"
        description="Permiten organizar diferentes secciones dentro de una misma pantalla."
      >
        <Tabs items={TAB_ITEMS} value={tab} onChange={setTab} />
        <p className="showcase__tab-content">{TAB_CONTENT[tab]}</p>
      </ShowcaseSection>

      <ShowcaseSection
        id="estados"
        title="Estados"
        description="Indicadores breves para mostrar el estado de una operación o elemento."
      >
        <div className="showcase__row">
          {BADGE_EXAMPLES.map((example) => (
            <Badge key={example.text} variant={example.variant}>
              {example.text}
            </Badge>
          ))}
        </div>
      </ShowcaseSection>

      <ShowcaseSection
        id="avisos"
        title="Avisos y notificaciones"
        description="Mensajes para comunicar información importante al usuario."
      >
        <SubHeading title="Avisos" description="Mensajes visibles que necesitan permanecer en pantalla." />
        <div className="showcase__stack">
          <Alert variant="info" title="Información">
            Los cambios se guardarán automáticamente.
          </Alert>
          <Alert variant="success" title="Operación completada">
            Los cambios se guardaron correctamente.
          </Alert>
          <Alert variant="warning" title="Atención">
            Revisa la información antes de continuar.
          </Alert>
          <Alert variant="danger" title="No se pudo completar la operación">
            Inténtalo nuevamente.
          </Alert>
        </div>

        <SubHeading
          title="Notificaciones emergentes"
          description="Mensajes breves que aparecen después de realizar una acción."
        />
        <div className="showcase__row">
          <Button variant="secondary" onClick={() => toast.success('Los cambios se guardaron correctamente.')}>
            Mostrar correcto
          </Button>
          <Button variant="secondary" onClick={() => toast.info('Información relevante para la operación.')}>
            Mostrar información
          </Button>
          <Button variant="secondary" onClick={() => toast.warning('Revisa este dato antes de continuar.')}>
            Mostrar advertencia
          </Button>
          <Button variant="secondary" onClick={() => toast.error('Ocurrió un error. Inténtalo nuevamente.')}>
            Mostrar error
          </Button>
        </div>
      </ShowcaseSection>

      <ShowcaseSection
        id="ventanas"
        title="Ventanas y confirmaciones"
        description="Se utilizan para mostrar información o confirmar acciones importantes."
      >
        <div className="showcase__row">
          <Button onClick={() => setDialogOpen(true)}>Abrir ventana</Button>
          <Button variant="danger" onClick={() => setConfirmOpen(true)}>
            Probar confirmación
          </Button>
        </div>
        <Dialog
          open={dialogOpen}
          onClose={() => setDialogOpen(false)}
          title="Editar información"
          description="Aquí puedes revisar o modificar los datos."
          footer={
            <>
              <Button variant="outline" onClick={() => setDialogOpen(false)}>
                Cancelar
              </Button>
              <Button onClick={() => setDialogOpen(false)}>Guardar</Button>
            </>
          }
        >
          Contenido de ejemplo de una ventana. Puede incluir cualquier información.
        </Dialog>
        <ConfirmDialog
          open={confirmOpen}
          onClose={() => setConfirmOpen(false)}
          onConfirm={() => {
            setConfirmOpen(false)
            toast.success('Elemento eliminado.')
          }}
          title="¿Eliminar este elemento?"
          description="Esta acción no se puede deshacer."
          confirmLabel="Eliminar"
          variant="danger"
        />
      </ShowcaseSection>

      <ShowcaseSection
        id="carga"
        title="Carga y ausencia de información"
        description="Estados utilizados mientras se obtiene información o cuando no existen resultados."
      >
        <SubHeading title="Sin resultados" />
        <EmptyState
          icon={<Search size={22} />}
          title="Sin resultados"
          description="No se encontró información con los filtros seleccionados."
          action={<Button size="sm">Limpiar filtros</Button>}
        />

        <SubHeading title="Cargando" />
        <LoadingState />

        <SubHeading title="Vista previa de carga" />
        <div className="showcase__stack">
          <Skeleton width="80%" />
          <Skeleton width="60%" />
          <Skeleton width={40} height={40} circle />
        </div>
      </ShowcaseSection>

      <ShowcaseSection
        id="teclado"
        title="Teclado numérico"
        description="Permite introducir cantidades y valores cómodamente en pantallas táctiles."
      >
        <div className="showcase__keypad-demo">
          <output className="showcase__keypad-value">{keypadValue || '0'}</output>
          <NumericKeypad
            value={keypadValue}
            onChange={setKeypadValue}
            onConfirm={(value) => toast.info(`Valor confirmado: ${value}`)}
          />
        </div>
      </ShowcaseSection>

      <ShowcaseSection
        id="menu"
        title="Menú de acciones"
        description="Agrupa acciones secundarias para mantener la interfaz ordenada."
      >
        <div className="showcase__row">
          <ActionMenu
            items={[
              { label: 'Editar', icon: <Pencil size={16} />, onSelect: () => toast.info('Editar') },
              { label: 'Duplicar', icon: <Plus size={16} />, onSelect: () => toast.info('Duplicar') },
              { label: 'Desactivar', icon: <Download size={16} />, onSelect: () => toast.info('Desactivar') },
              {
                label: 'Eliminar',
                icon: <Trash2 size={16} />,
                danger: true,
                onSelect: () => toast.error('Eliminar'),
              },
            ]}
          />
        </div>
      </ShowcaseSection>

      <ShowcaseSection
        id="listados"
        title="Listados"
        description="Ejemplo de cómo se mostrarán conjuntos de información."
      >
        <DataList
          columns={[
            { key: 'name', header: 'Nombre', render: (row: DemoRow) => row.name },
            {
              key: 'status',
              header: 'Estado',
              render: (row: DemoRow) => (
                <Badge variant={row.status === 'Activo' ? 'success' : 'warning'}>{row.status}</Badge>
              ),
            },
          ]}
          rows={DEMO_ROWS}
          rowKey={(row) => row.id}
        />
      </ShowcaseSection>
    </div>
  )
}
