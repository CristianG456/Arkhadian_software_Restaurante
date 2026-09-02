import { useCallback, useEffect, useState } from 'react'
import * as areasApi from '../api/areas'
import * as tablesApi from '../api/tables'
import { ApiClientError } from '../api/ApiClientError'
import type { Area } from '../api/areas'

function userMessage(error: unknown): string {
  return error instanceof ApiClientError ? error.userMessage : 'No pudimos completar la operación.'
}

export function useAreas() {
  const [areas, setAreas] = useState<Area[]>([])
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async (signal?: AbortSignal, showLoading = true) => {
    if (showLoading) setLoading(true)
    try {
      const response = await areasApi.fetchAreas(signal)
      setAreas(response.data.areas)
      setError(null)
    } catch (cause) {
      if (signal?.aborted) return
      setError(userMessage(cause))
    } finally {
      if (!signal?.aborted && showLoading) setLoading(false)
    }
  }, [])

  useEffect(() => {
    const controller = new AbortController()
    const startId = window.setTimeout(() => void load(controller.signal), 0)
    return () => {
      window.clearTimeout(startId)
      controller.abort()
    }
  }, [load])

  async function mutate<T>(operation: () => Promise<T>): Promise<T> {
    setSaving(true)
    try {
      const result = await operation()
      await load(undefined, false)
      return result
    } catch (cause) {
      setError(userMessage(cause))
      throw cause
    } finally {
      setSaving(false)
    }
  }

  const refresh = useCallback(() => load(), [load])
  const clearError = useCallback(() => setError(null), [])

  return {
    areas,
    loading,
    saving,
    error,
    clearError,
    refresh,
    createArea: (name: string) => mutate(() => areasApi.createArea(name)),
    updateArea: (id: string, changes: { name?: string; isActive?: boolean }) =>
      mutate(() => areasApi.updateArea(id, changes)),
    deleteArea: (id: string) => mutate(() => areasApi.deleteArea(id)),
    reorderAreas: (ids: string[]) => mutate(() => areasApi.reorderAreas(ids)),
    createTable: (areaId: string, label: string) => mutate(() => tablesApi.createTable(areaId, label)),
    updateTable: (id: string, changes: { label?: string; isActive?: boolean }) =>
      mutate(() => tablesApi.updateTable(id, changes)),
    moveTable: (id: string, areaId: string) => mutate(() => tablesApi.moveTable(id, areaId)),
    deleteTable: (id: string) => mutate(() => tablesApi.deleteTable(id)),
    bulkCreateTables: (areaId: string, from: number, to: number) =>
      mutate(() => tablesApi.bulkCreateTables(areaId, from, to)),
    reorderTables: (areaId: string, ids: string[]) => mutate(() => tablesApi.reorderTables(areaId, ids)),
  }
}

export function getApiErrorMessage(error: unknown): string {
  return userMessage(error)
}
