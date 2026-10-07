import { apiRequest } from './client'
import type { ApiSuccess } from './types'

export interface MobileDevice {
  id: string
  name: string
  platform: string
  status: 'ACTIVE' | 'REVOKED'
  pairedAt: string
  lastSeenAt: string | null
  revokedAt: string | null
}
export interface DevicePairing {
  id: string
  restaurantCode: string
  pairingCode: string
  pairingToken: string
  qrPayload: string
  expiresAt: string
}
export const fetchInstallation = (signal?: AbortSignal) =>
  apiRequest<ApiSuccess<{ installation: { id: string; restaurantCode: string; restaurantName: string; operatingMode: string } }>>(
    '/api/mobile/installation',
    { signal },
  )
export const fetchDevices = (signal?: AbortSignal) =>
  apiRequest<ApiSuccess<{ devices: MobileDevice[] }>>('/api/mobile/devices', { signal })
export const createPairing = () =>
  apiRequest<ApiSuccess<{ pairing: DevicePairing }>>('/api/mobile/pairings', {
    method: 'POST',
    body: { minutes: 10 },
  })
export const revokeDevice = (id: string) =>
  apiRequest('/api/mobile/devices/' + id + '/revoke', { method: 'POST' })
