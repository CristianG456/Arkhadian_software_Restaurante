import { apiRequest } from './client'
import type { ApiSuccess } from './types'

export interface RestaurantTable {
  id: string
  areaId: string
  label: string
  displayName: string
  sortOrder: number
  isActive: boolean
  createdAt: string
  updatedAt: string
}

export interface Area {
  id: string
  name: string
  sortOrder: number
  isActive: boolean
  tablesCount: number
  activeTablesCount: number
  inactiveTablesCount: number
  tables: RestaurantTable[]
  createdAt: string
  updatedAt: string
}

export function fetchAreas(signal?: AbortSignal) {
  return apiRequest<ApiSuccess<{ areas: Area[] }>>('/api/areas', { signal })
}

export function createArea(name: string) {
  return apiRequest<ApiSuccess<{ area: Area }>>('/api/areas', {
    method: 'POST',
    body: { name },
  })
}

export function updateArea(id: string, changes: { name?: string; isActive?: boolean }) {
  return apiRequest<ApiSuccess<{ area: Area }>>(`/api/areas/${id}`, {
    method: 'PUT',
    body: changes,
  })
}

export function deleteArea(id: string) {
  return apiRequest<ApiSuccess<{ deletedId: string }>>(`/api/areas/${id}`, { method: 'DELETE' })
}

export function reorderAreas(ids: string[]) {
  return apiRequest<ApiSuccess<{ areas: Area[] }>>('/api/areas/reorder', {
    method: 'POST',
    body: { items: ids.map((id, index) => ({ id, sortOrder: index + 1 })) },
  })
}
