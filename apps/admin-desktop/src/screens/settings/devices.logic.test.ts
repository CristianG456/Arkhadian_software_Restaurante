import { describe, expect, it } from 'vitest'
import { deriveConnectivity } from '../../app/connectivity.logic'
import type { SystemStatusData } from '../../api/types'
import { devicesViewState } from './devices.logic'

const data: SystemStatusData = {
  core: { status: 'online', version: 'test' },
  database: { status: 'connected', latencyMs: 1 },
  localServer: { status: 'online', host: '127.0.0.1', port: 8000 },
  internet: { status: 'online' },
  installationId: 'test',
  checkedAt: new Date(0).toISOString(),
}

describe('devices connectivity state', () => {
  it('shows a load error and disables linking while Core is offline', () => {
    const state = devicesViewState(deriveConnectivity('OFFLINE', null), false, null, 0)
    expect(state.mode).toBe('error')
    expect(state.message).toContain('Restaurant Core')
    expect(state.canLink).toBe(false)
  })

  it('distinguishes a real empty response from a failed load', () => {
    const state = devicesViewState(deriveConnectivity('ONLINE', data), false, null, 0)
    expect(state.mode).toBe('empty')
    expect(state.canLink).toBe(true)
  })

  it('returns to the list state after connectivity recovers', () => {
    const state = devicesViewState(deriveConnectivity('ONLINE', data), false, null, 2)
    expect(state.mode).toBe('list')
    expect(state.canLink).toBe(true)
  })
})
