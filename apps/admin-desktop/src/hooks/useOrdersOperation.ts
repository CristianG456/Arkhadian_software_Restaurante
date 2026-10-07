import { useCallback, useEffect, useState } from 'react'
import * as api from '../api/operations'
import { ApiClientError } from '../api/ApiClientError'
import { useSystemStatusContext } from '../app/SystemStatusContext'
export const operationError = (error: unknown) => error instanceof ApiClientError ? error.userMessage : 'No pudimos completar la operación.'
export function useOrdersOperation() {
  const system = useSystemStatusContext(); const available = system.snapshot.localOperationAvailable
  const [areas, setAreas] = useState<api.OperationArea[]>([]); const [staff, setStaff] = useState<api.StaffMember[]>([])
  const [loading, setLoading] = useState(true); const [saving, setSaving] = useState(false); const [error, setError] = useState<string | null>(null)
  const load = useCallback(async (signal?: AbortSignal) => {
    try { const [operation, people] = await Promise.all([api.fetchOperations(signal), api.fetchStaff(signal)]); setAreas(operation.data.areas); setStaff(people.data.staff); setError(null) }
    catch (cause) { if (!signal?.aborted) setError(operationError(cause)) } finally { if (!signal?.aborted) setLoading(false) }
  }, [])
  useEffect(() => {
    if (!available) {
      const unavailableId = window.setTimeout(() => {
        if (system.connectivity !== 'CHECKING') setLoading(false)
      }, 0)
      return () => window.clearTimeout(unavailableId)
    }
    const controller = new AbortController()
    let timer: number | null = null
    let active = true
    const tick = async () => {
      await load(controller.signal)
      if (active) timer = window.setTimeout(() => void tick(), 10000)
    }
    timer = window.setTimeout(() => void tick(), 0)
    return () => {
      active = false
      controller.abort()
      if (timer !== null) window.clearTimeout(timer)
    }
  }, [available, load, system.connectivity])
  async function mutate<T>(action: () => Promise<T>) { setSaving(true); try { const result = await action(); await load(); return result } finally { setSaving(false) } }
  return { areas, staff, loading, saving, error, refresh: load, fetchSession: api.fetchSession,
    createOrder: (body: Parameters<typeof api.createOrder>[0]) => mutate(() => api.createOrder(body)),
    transferWaiter: (id: string, waiter: string, reason?: string) => mutate(() => api.transferWaiter(id, waiter, reason)),
    moveSession: (id: string, table: string, reason?: string) => mutate(() => api.moveSession(id, table, reason)),
    cancelOrder: (id: string, reason: string) => mutate(() => api.cancelOrder(id, reason)),
    cancelItem: (id: string, reason: string) => mutate(() => api.cancelItem(id, reason)),
    createStaff: (name: string) => mutate(() => api.createStaff(name)),
    updateStaff: (id: string, body: Parameters<typeof api.updateStaff>[1]) => mutate(() => api.updateStaff(id, body)),
  }
}
