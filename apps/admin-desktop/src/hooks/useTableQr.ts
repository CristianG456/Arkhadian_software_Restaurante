import { useCallback, useEffect, useState } from 'react'
import { ApiClientError } from '../api/ApiClientError'
import * as tableQrApi from '../api/tableQr'
import type { TableQrCredential } from '../api/tableQr'

function messageFor(error: unknown) {
  return error instanceof ApiClientError
    ? error.userMessage
    : 'No pudimos completar la operación del QR.'
}

export function useTableQr(tableId: string) {
  const [credential, setCredential] = useState<TableQrCredential | null>(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async (signal?: AbortSignal) => {
    setLoading(true)
    try {
      const response = await tableQrApi.fetchTableQr(tableId, signal)
      setCredential(response.data.credential)
      setError(null)
    } catch (cause) {
      if (signal?.aborted) return
      if (cause instanceof ApiClientError && cause.code === 'QR_NOT_FOUND') {
        setCredential(null)
        setError(null)
      } else {
        setError(messageFor(cause))
      }
    } finally {
      if (!signal?.aborted) setLoading(false)
    }
  }, [tableId])

  useEffect(() => {
    const controller = new AbortController()
    const startId = window.setTimeout(() => void load(controller.signal), 0)
    return () => {
      window.clearTimeout(startId)
      controller.abort()
    }
  }, [load])

  async function mutate(operation: () => Promise<{ data: { credential: TableQrCredential } }>) {
    setSaving(true)
    setError(null)
    try {
      const response = await operation()
      setCredential(response.data.credential)
      return response.data.credential
    } catch (cause) {
      setError(messageFor(cause))
      throw cause
    } finally {
      setSaving(false)
    }
  }

  return {
    credential,
    loading,
    saving,
    error,
    generate: () => mutate(() => tableQrApi.generateTableQr(tableId)),
    regenerate: () => mutate(() => tableQrApi.regenerateTableQr(tableId)),
    revoke: () => mutate(() => tableQrApi.revokeTableQr(tableId)),
  }
}
