import type { Area, RestaurantTable } from '../../api/areas'

export type StatusFilter = 'active' | 'inactive' | 'all'

export function matchesStatus(isActive: boolean, filter: StatusFilter): boolean {
  return filter === 'all' || (filter === 'active' ? isActive : !isActive)
}

export function filterTables(tables: RestaurantTable[], query: string, filter: StatusFilter) {
  const normalizedQuery = query.trim().toLocaleLowerCase('es')
  return tables.filter((table) =>
    matchesStatus(table.isActive, filter) &&
    (!normalizedQuery || table.displayName.toLocaleLowerCase('es').includes(normalizedQuery)),
  )
}

export function filterAreas(areas: Area[], filter: StatusFilter) {
  return areas.filter((area) => matchesStatus(area.isActive, filter))
}

export function moveId(ids: string[], id: string, direction: -1 | 1): string[] {
  const index = ids.indexOf(id)
  const target = index + direction
  if (index < 0 || target < 0 || target >= ids.length) return ids
  const next = [...ids]
  const [moved] = next.splice(index, 1)
  if (moved === undefined) return ids
  next.splice(target, 0, moved)
  return next
}

export function bulkPreview(from: number, to: number, tables: RestaurantTable[]) {
  if (!Number.isInteger(from) || !Number.isInteger(to) || from < 1 || to < from || to - from + 1 > 100) {
    return { valid: false, newLabels: [] as string[], existingLabels: [] as string[] }
  }
  const existing = new Set(tables.map((table) => table.label.toLocaleLowerCase('es')))
  const labels = Array.from({ length: to - from + 1 }, (_, index) => String(from + index))
  return {
    valid: true,
    newLabels: labels.filter((label) => !existing.has(label.toLocaleLowerCase('es'))),
    existingLabels: labels.filter((label) => existing.has(label.toLocaleLowerCase('es'))),
  }
}

export function tableEmptyStateTitle(
  totalTables: number,
  query: string,
  filter: StatusFilter,
): string {
  if (totalTables === 0) return 'Esta área todavía no tiene mesas'
  if (query.trim()) return 'No encontramos mesas'
  if (filter === 'inactive') return 'No hay mesas desactivadas en esta área'
  if (filter === 'active') return 'No hay mesas activas en esta área'
  return 'No hay mesas en este filtro'
}
