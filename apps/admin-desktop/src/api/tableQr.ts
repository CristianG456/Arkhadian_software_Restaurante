import { apiRequest } from './client'
import type { ApiSuccess } from './types'

export type TableQrStatus = 'active' | 'revoked'

export interface TableQrCredential {
  id: string
  tableId: string
  publicToken: string
  publicUrl: string | null
  version: number
  status: TableQrStatus
  createdAt: string
  revokedAt: string | null
  updatedAt: string
}

type CredentialResponse = ApiSuccess<{ credential: TableQrCredential }>

export function fetchTableQr(tableId: string, signal?: AbortSignal) {
  return apiRequest<CredentialResponse>(`/api/tables/${tableId}/qr`, { signal })
}

export function generateTableQr(tableId: string) {
  return apiRequest<CredentialResponse>(`/api/tables/${tableId}/qr`, { method: 'POST' })
}

export function regenerateTableQr(tableId: string) {
  return apiRequest<CredentialResponse>(`/api/tables/${tableId}/qr/regenerate`, { method: 'POST' })
}

export function revokeTableQr(tableId: string) {
  return apiRequest<CredentialResponse>(`/api/tables/${tableId}/qr/revoke`, { method: 'POST' })
}
