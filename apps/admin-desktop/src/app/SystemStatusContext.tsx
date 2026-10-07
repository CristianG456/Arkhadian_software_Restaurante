import { createContext, useContext, useEffect, useRef } from 'react'
import type { ReactNode } from 'react'
import { useSystemStatus } from '../hooks/useSystemStatus'
import type { UseSystemStatusResult } from '../hooks/useSystemStatus'
import { useToast } from '../components/ui/ToastProvider'
import { connectivityEvents, INITIAL_CONNECTIVITY } from './connectivity.logic'
import type { ConnectivitySnapshot } from './connectivity.logic'

const SystemStatusContext = createContext<UseSystemStatusResult | null>(null)

export function SystemStatusProvider({ children }: { children: ReactNode }) {
  const status = useSystemStatus()
  const toast = useToast()
  const previous = useRef<ConnectivitySnapshot>(INITIAL_CONNECTIVITY)

  useEffect(() => {
    const events = connectivityEvents(previous.current, status.snapshot)
    previous.current = status.snapshot

    for (const event of events) {
      if (event === 'CORE_UNAVAILABLE') {
        console.warn('[connectivity] Restaurant Core transitioned to unavailable')
        toast.dismiss('CONNECTION_RESTORED')
        toast.dismiss('DATABASE_UNAVAILABLE')
        toast.error(
          'No se puede conectar con Restaurant Core. Reconectando automáticamente.',
          6000,
          'CORE_UNAVAILABLE',
        )
      } else if (event === 'CORE_RESTORED') {
        console.info('[connectivity] Restaurant Core connection restored')
        toast.dismiss('CORE_UNAVAILABLE')
        toast.success('Conexión con Restaurant Core restaurada.', 4000, 'CONNECTION_RESTORED')
      } else if (event === 'DATABASE_UNAVAILABLE') {
        console.warn('[connectivity] Local database transitioned to unavailable')
        toast.dismiss('DATABASE_RESTORED')
        toast.error('Base de datos local no disponible.', 6000, 'DATABASE_UNAVAILABLE')
      } else {
        console.info('[connectivity] Local database connection restored')
        toast.dismiss('DATABASE_UNAVAILABLE')
        toast.success('Conexión con la base de datos restaurada.', 4000, 'DATABASE_RESTORED')
      }
    }
  }, [status.snapshot, toast])

  return <SystemStatusContext.Provider value={status}>{children}</SystemStatusContext.Provider>
}

export function useSystemStatusContext(): UseSystemStatusResult {
  const ctx = useContext(SystemStatusContext)
  if (!ctx) throw new Error('useSystemStatusContext debe usarse dentro de <SystemStatusProvider>')
  return ctx
}
