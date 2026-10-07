import { useCallback, useEffect, useRef, useState } from 'react'
import { fetchSystemStatus } from '../api/system'
import { ApiClientError } from '../api/ApiClientError'
import type { SystemStatusData } from '../api/types'
import { connectivityAfterFailure, deriveConnectivity, INITIAL_CONNECTIVITY, retryDelay } from '../app/connectivity.logic'
import type { ConnectivitySnapshot } from '../app/connectivity.logic'
import { PollingLifecycle } from '../app/pollingLifecycle'
import { env } from '../config/env'

export type CoreConnectivityState = 'CHECKING' | 'ONLINE' | 'OFFLINE' | 'ERROR'

export interface SystemStatusState {
  connectivity: CoreConnectivityState
  data: SystemStatusData | null
  snapshot: ConnectivitySnapshot
  lastCheckedAt: Date | null
  lastError: string | null
  refreshing: boolean
}

const STARTUP_GRACE_MS = 6000

export interface UseSystemStatusResult extends SystemStatusState {
  refresh: () => Promise<void>
}

export function useSystemStatus(): UseSystemStatusResult {
  const [state, setState] = useState<SystemStatusState>({
    connectivity: 'CHECKING',
    data: null,
    snapshot: INITIAL_CONNECTIVITY,
    lastCheckedAt: null,
    lastError: null,
    refreshing: false,
  })
  const refreshRef = useRef<() => Promise<void>>(async () => undefined)

  useEffect(() => {
    const lifecycle = new PollingLifecycle((id) => window.clearTimeout(id))
    lifecycle.start()
    let requestSequence = 0
    let failureCount = 0
    let hasConnected = false
    const mountedAt = Date.now()

    const schedule = (delay: number) => {
      if (!lifecycle.active) return
      lifecycle.replaceTimer(window.setTimeout(() => void check(false), delay))
    }

    const check = async (manual: boolean) => {
      if (!lifecycle.active) return
      lifecycle.clearTimer()

      const controller = new AbortController()
      lifecycle.replaceRequest(controller)
      const sequence = ++requestSequence
      if (manual) setState((previous) => ({ ...previous, refreshing: true }))

      try {
        const response = await fetchSystemStatus(controller.signal)
        if (!lifecycle.active || sequence !== requestSequence) return
        hasConnected = true
        failureCount = 0
        setState({
          connectivity: 'ONLINE',
          data: response.data,
          snapshot: deriveConnectivity('ONLINE', response.data),
          lastCheckedAt: new Date(),
          lastError: null,
          refreshing: false,
        })
        schedule(env.statusPollIntervalMs)
      } catch (error) {
        if (!lifecycle.active || sequence !== requestSequence || controller.signal.aborted) return
        const apiError = error instanceof ApiClientError
          ? error
          : new ApiClientError('network', 'Unknown error', { cause: error })
        const elapsedMs = Date.now() - mountedAt
        const connectivity: CoreConnectivityState = connectivityAfterFailure(
          hasConnected,
          elapsedMs,
          apiError.kind === 'invalid_response',
          STARTUP_GRACE_MS,
        )
        const withinStartupGrace = connectivity === 'CHECKING'

        if (!withinStartupGrace) failureCount += 1
        setState((previous) => ({
          connectivity,
          data: withinStartupGrace ? previous.data : null,
          snapshot: withinStartupGrace ? previous.snapshot : deriveConnectivity(connectivity, null),
          lastCheckedAt: new Date(),
          lastError: apiError.userMessage,
          refreshing: false,
        }))
        schedule(
          withinStartupGrace
            ? Math.min(env.statusPollIntervalMs, Math.max(100, STARTUP_GRACE_MS - elapsedMs))
            : retryDelay(env.statusPollIntervalMs, failureCount, env.statusPollMaxIntervalMs),
        )
      }
    }

    refreshRef.current = () => check(true)
    schedule(0)

    return () => {
      requestSequence += 1
      lifecycle.stop()
      refreshRef.current = async () => undefined
    }
  }, [])

  const refresh = useCallback(() => refreshRef.current(), [])
  return { ...state, refresh }
}
