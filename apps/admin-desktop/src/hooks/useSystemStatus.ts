import { useCallback, useEffect, useRef, useState } from 'react'
import { fetchSystemStatus } from '../api/system'
import { ApiClientError } from '../api/ApiClientError'
import type { SystemStatusData } from '../api/types'
import { env } from '../config/env'

/**
 * Admin's own read on whether Restaurant Core is reachable at all —
 * distinct from `data.core.status`, which is what Core reports about
 * itself once it *does* answer. See docs/architecture.md "Connectivity
 * states".
 */
export type ConnectivityState = 'CHECKING' | 'ONLINE' | 'OFFLINE' | 'ERROR'

export interface SystemStatusState {
  connectivity: ConnectivityState
  data: SystemStatusData | null
  lastCheckedAt: Date | null
  lastError: string | null
}

// A Core that is merely slow to boot must not flash "offline" at startup
// (see docs/architecture.md "Startup tolerance") — failures inside this
// window keep showing CHECKING instead.
const STARTUP_GRACE_MS = 6000

export interface UseSystemStatusResult extends SystemStatusState {
  /** Re-checks immediately, outside the regular poll interval. */
  refresh: () => void
}

export function useSystemStatus(): UseSystemStatusResult {
  const [state, setState] = useState<SystemStatusState>({
    connectivity: 'CHECKING',
    data: null,
    lastCheckedAt: null,
    lastError: null,
  })

  // Set from inside the effect below, never at render time (Date.now() is
  // impure and must not run during render).
  const mountedAtRef = useRef(0)
  const hasConnectedRef = useRef(false)

  const check = useCallback(async () => {
    try {
      const response = await fetchSystemStatus()
      hasConnectedRef.current = true
      setState({
        connectivity: 'ONLINE',
        data: response.data,
        lastCheckedAt: new Date(),
        lastError: null,
      })
    } catch (error) {
      const apiError =
        error instanceof ApiClientError
          ? error
          : new ApiClientError('network', 'Unknown error', { cause: error })

      const withinStartupGrace =
        !hasConnectedRef.current && Date.now() - mountedAtRef.current < STARTUP_GRACE_MS

      console.error('[system-status]', apiError.kind, apiError.message, apiError.cause)

      setState((previous) => ({
        connectivity: withinStartupGrace
          ? 'CHECKING'
          : apiError.kind === 'invalid_response'
            ? 'ERROR'
            : 'OFFLINE',
        data: withinStartupGrace ? previous.data : null,
        lastCheckedAt: new Date(),
        lastError: apiError.userMessage,
      }))
    }
  }, [])

  useEffect(() => {
    mountedAtRef.current = Date.now()

    // The first check is scheduled through a timer callback (same as the
    // interval below) rather than invoked directly in the effect body, so
    // every state update this hook makes originates from an external
    // system (the timer), not from synchronous effect execution.
    const startId = window.setTimeout(() => void check(), 0)
    const intervalId = window.setInterval(() => void check(), env.statusPollIntervalMs)

    return () => {
      window.clearTimeout(startId)
      window.clearInterval(intervalId)
    }
  }, [check])

  return { ...state, refresh: () => void check() }
}
