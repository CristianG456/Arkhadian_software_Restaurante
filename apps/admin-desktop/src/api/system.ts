import { apiRequest } from './client'
import type { ApiSuccess, HealthResponse, SystemStatusData } from './types'

export function fetchHealth(signal?: AbortSignal): Promise<HealthResponse> {
  return apiRequest<HealthResponse>('/api/health', { signal })
}

export function fetchSystemStatus(signal?: AbortSignal): Promise<ApiSuccess<SystemStatusData>> {
  return apiRequest<ApiSuccess<SystemStatusData>>('/api/system/status', { signal })
}
