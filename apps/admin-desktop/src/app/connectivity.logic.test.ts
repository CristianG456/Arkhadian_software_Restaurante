import { describe, expect, it } from 'vitest'
import type { SystemStatusData } from '../api/types'
import {
  connectivityAfterFailure,
  connectivityEvents,
  deriveConnectivity,
  INITIAL_CONNECTIVITY,
  retryDelay,
} from './connectivity.logic'

function status(database: 'connected' | 'disconnected', internet: 'online' | 'offline'): SystemStatusData {
  return {
    core: { status: 'online', version: 'test' },
    database: { status: database, latencyMs: database === 'connected' ? 1 : null },
    localServer: { status: 'online', host: '127.0.0.1', port: 8000 },
    internet: { status: internet },
    installationId: 'test',
    checkedAt: new Date(0).toISOString(),
  }
}

const offline = deriveConnectivity('OFFLINE', null)
const online = deriveConnectivity('ONLINE', status('connected', 'online'))

describe('global connectivity transitions', () => {
  it('emits one notification for ONLINE to OFFLINE and none for repeated failures', () => {
    expect(connectivityEvents(online, offline)).toEqual(['CORE_UNAVAILABLE'])
    let previous = offline
    const repeated = Array.from({ length: 10 }, () => {
      const events = connectivityEvents(previous, offline)
      previous = offline
      return events
    })
    expect(repeated.flat()).toEqual([])
  })

  it('emits recovery once and permits a new later outage notification', () => {
    expect(connectivityEvents(offline, online)).toEqual(['CORE_RESTORED'])
    expect(connectivityEvents(online, online)).toEqual([])
    expect(connectivityEvents(online, offline)).toEqual(['CORE_UNAVAILABLE'])
  })

  it('does not recreate a manually closed toast during the same incident', () => {
    const active = new Set<string>()
    for (const event of connectivityEvents(online, offline)) active.add(event)
    active.delete('CORE_UNAVAILABLE')
    for (let attempt = 0; attempt < 10; attempt += 1) {
      for (const event of connectivityEvents(offline, offline)) active.add(event)
    }
    expect(active.has('CORE_UNAVAILABLE')).toBe(false)
    expect(connectivityEvents(offline, online)).toContain('CORE_RESTORED')
    expect(connectivityEvents(online, offline)).toContain('CORE_UNAVAILABLE')
  })

  it('keeps local operation available when only Internet is offline', () => {
    const localOnly = deriveConnectivity('ONLINE', status('connected', 'offline'))
    expect(localOnly.localOperationAvailable).toBe(true)
    expect(localOnly.components.internet).toBe('OFFLINE')
    expect(connectivityEvents(online, localOnly)).toEqual([])
  })

  it('reports database outage independently from Core', () => {
    const databaseOffline = deriveConnectivity('ONLINE', status('disconnected', 'online'))
    expect(databaseOffline.components.core).toBe('ONLINE')
    expect(databaseOffline.components.database).toBe('OFFLINE')
    expect(databaseOffline.localOperationAvailable).toBe(false)
    expect(connectivityEvents(online, databaseOffline)).toEqual(['DATABASE_UNAVAILABLE'])
  })

  it('stays CHECKING throughout startup grace and avoids an early outage event', () => {
    expect(connectivityAfterFailure(false, 1000, false, 6000)).toBe('CHECKING')
    expect(connectivityEvents(INITIAL_CONNECTIVITY, INITIAL_CONNECTIVITY)).toEqual([])
    expect(connectivityAfterFailure(false, 6000, false, 6000)).toBe('OFFLINE')
  })

  it('uses bounded reconnect backoff and resets to the base interval', () => {
    expect([1, 2, 3, 4, 5, 6].map((attempt) => retryDelay(5000, attempt, 30000)))
      .toEqual([5000, 5000, 10000, 10000, 15000, 30000])
    expect(retryDelay(5000, 1, 30000)).toBe(5000)
  })
})
