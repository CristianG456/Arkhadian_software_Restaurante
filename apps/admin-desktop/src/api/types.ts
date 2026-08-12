/**
 * Contracts for Restaurant Core's REST API. Keep these in sync with
 * services/restaurant-core/app/Domain/System — this is the only place the
 * frontend should describe that shape.
 */

export interface HealthResponse {
  status: 'ok'
  service: string
  version: string
}

export type BinaryStatus = 'online' | 'offline'
export type DatabaseConnectionStatus = 'connected' | 'disconnected'
export type CoreLiveStatus = 'online' | 'degraded'

export interface SystemStatusData {
  core: {
    status: CoreLiveStatus
    version: string
  }
  database: {
    status: DatabaseConnectionStatus
    latencyMs: number | null
  }
  localServer: {
    status: BinaryStatus
    host: string
    port: number
  }
  internet: {
    status: BinaryStatus
  }
  installationId: string
  checkedAt: string
}

export interface ApiSuccess<T> {
  success: true
  data: T
}

export interface ApiErrorBody {
  success: false
  error: {
    code: string
    message: string
  }
}
