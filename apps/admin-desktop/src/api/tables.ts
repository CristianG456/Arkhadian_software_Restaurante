import { apiRequest } from './client'
import type { ApiSuccess } from './types'
import type { RestaurantTable } from './areas'

export function createTable(areaId: string, label: string) {
  return apiRequest<ApiSuccess<{ table: RestaurantTable }>>(`/api/areas/${areaId}/tables`, {
    method: 'POST', body: { label },
  })
}

export function updateTable(id: string, changes: { label?: string; isActive?: boolean }) {
  return apiRequest<ApiSuccess<{ table: RestaurantTable }>>(`/api/tables/${id}`, {
    method: 'PUT', body: changes,
  })
}

export function moveTable(id: string, areaId: string) {
  return apiRequest<ApiSuccess<{ table: RestaurantTable }>>(`/api/tables/${id}/move`, {
    method: 'POST',
    body: { areaId },
  })
}

export function deleteTable(id: string) {
  return apiRequest<ApiSuccess<{ deletedId: string }>>(`/api/tables/${id}`, { method: 'DELETE' })
}

export interface BulkTablesResult {
  created: RestaurantTable[]
  skippedExisting: RestaurantTable[]
  createdCount: number
  skippedCount: number
}

export function bulkCreateTables(areaId: string, from: number, to: number) {
  return apiRequest<ApiSuccess<BulkTablesResult>>(`/api/areas/${areaId}/tables/bulk`, {
    method: 'POST', body: { from, to },
  })
}

export function reorderTables(areaId: string, ids: string[]) {
  return apiRequest<ApiSuccess<{ tables: RestaurantTable[] }>>(`/api/areas/${areaId}/tables/reorder`, {
    method: 'POST',
    body: { items: ids.map((id, index) => ({ id, sortOrder: index + 1 })) },
  })
}
