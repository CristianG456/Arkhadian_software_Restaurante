import type { SystemStatusData } from '../api/types'

export type ConnectivityState = 'CHECKING' | 'ONLINE' | 'OFFLINE' | 'DEGRADED' | 'ERROR'

export interface ConnectivityComponents {
  core: ConnectivityState
  database: ConnectivityState
  internet: ConnectivityState
  localServer: ConnectivityState
}

export interface ConnectivitySnapshot {
  components: ConnectivityComponents
  localOperationAvailable: boolean
}

export type ConnectivityEvent =
  | 'CORE_UNAVAILABLE'
  | 'CORE_RESTORED'
  | 'DATABASE_UNAVAILABLE'
  | 'DATABASE_RESTORED'

export const INITIAL_CONNECTIVITY: ConnectivitySnapshot = {
  components: {
    core: 'CHECKING',
    database: 'CHECKING',
    internet: 'CHECKING',
    localServer: 'CHECKING',
  },
  localOperationAvailable: false,
}

export function deriveConnectivity(
  coreConnectivity: Exclude<ConnectivityState, 'DEGRADED'>,
  data: SystemStatusData | null,
): ConnectivitySnapshot {
  if (coreConnectivity !== 'ONLINE' || !data) {
    const downstreamState: ConnectivityState = coreConnectivity === 'CHECKING' ? 'CHECKING' : 'ERROR'
    return {
      components: {
        core: coreConnectivity,
        database: downstreamState,
        internet: downstreamState,
        localServer: downstreamState,
      },
      localOperationAvailable: false,
    }
  }

  const core = data.core.status === 'degraded' ? 'DEGRADED' : 'ONLINE'
  const database = data.database.status === 'connected' ? 'ONLINE' : 'OFFLINE'
  const internet = data.internet.status === 'online' ? 'ONLINE' : 'OFFLINE'
  const localServer = data.localServer.status === 'online' ? 'ONLINE' : 'OFFLINE'

  return {
    components: { core, database, internet, localServer },
    localOperationAvailable: database === 'ONLINE' && localServer === 'ONLINE',
  }
}

function coreIsReachable(state: ConnectivityState): boolean {
  return state === 'ONLINE' || state === 'DEGRADED'
}

function coreIsUnavailable(state: ConnectivityState): boolean {
  return state === 'OFFLINE' || state === 'ERROR'
}

/** Infrastructure notifications are emitted from state transitions only. */
export function connectivityEvents(
  previous: ConnectivitySnapshot,
  current: ConnectivitySnapshot,
): ConnectivityEvent[] {
  const events: ConnectivityEvent[] = []
  const previousCore = previous.components.core
  const currentCore = current.components.core

  if (!coreIsUnavailable(previousCore) && coreIsUnavailable(currentCore)) {
    events.push('CORE_UNAVAILABLE')
  } else if (coreIsUnavailable(previousCore) && coreIsReachable(currentCore)) {
    events.push('CORE_RESTORED')
  }

  if (coreIsReachable(currentCore)) {
    if (previous.components.database !== 'OFFLINE' && current.components.database === 'OFFLINE') {
      events.push('DATABASE_UNAVAILABLE')
    } else if (
      previous.components.database === 'OFFLINE' &&
      current.components.database === 'ONLINE'
    ) {
      events.push('DATABASE_RESTORED')
    }
  }

  return events
}

export function retryDelay(baseMs: number, failureCount: number, maximumMs: number): number {
  const multipliers = [1, 1, 2, 2, 3, 6]
  const multiplier = multipliers[Math.min(Math.max(failureCount - 1, 0), multipliers.length - 1)] ?? 1
  return Math.min(baseMs * multiplier, maximumMs)
}

export function connectivityAfterFailure(
  hasConnected: boolean,
  elapsedMs: number,
  invalidResponse: boolean,
  startupGraceMs: number,
): Exclude<ConnectivityState, 'DEGRADED'> {
  if (!hasConnected && elapsedMs < startupGraceMs) return 'CHECKING'
  return invalidResponse ? 'ERROR' : 'OFFLINE'
}
