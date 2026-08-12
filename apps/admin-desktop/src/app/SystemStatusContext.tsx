import { createContext, useContext } from 'react'
import type { ReactNode } from 'react'
import { useSystemStatus } from '../hooks/useSystemStatus'
import type { UseSystemStatusResult } from '../hooks/useSystemStatus'

const SystemStatusContext = createContext<UseSystemStatusResult | null>(null)

/**
 * Polls Core exactly once for the whole app - the Topbar's compact
 * indicator and the full Diagnostico screen both read this instead of
 * each running their own useSystemStatus() (which would double the
 * polling traffic and could show two slightly different timestamps).
 */
export function SystemStatusProvider({ children }: { children: ReactNode }) {
  const status = useSystemStatus()
  return <SystemStatusContext.Provider value={status}>{children}</SystemStatusContext.Provider>
}

export function useSystemStatusContext(): UseSystemStatusResult {
  const ctx = useContext(SystemStatusContext)
  if (!ctx) throw new Error('useSystemStatusContext debe usarse dentro de <SystemStatusProvider>')
  return ctx
}
