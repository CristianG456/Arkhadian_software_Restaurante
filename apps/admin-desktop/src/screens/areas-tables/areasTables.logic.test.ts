import { describe, expect, it } from 'vitest'
import type { RestaurantTable } from '../../api/areas'
import { bulkPreview, filterTables, moveId } from './areasTables.logic'

function table(label: string, isActive = true): RestaurantTable {
  return { id: label, areaId: 'area', label, displayName: `Mesa ${label}`, sortOrder: 1, isActive, createdAt: '', updatedAt: '' }
}

describe('areas and tables UI logic', () => {
  it('filters by status and visible table name', () => {
    expect(filterTables([table('12'), table('A1', false)], '12', 'active').map((item) => item.label)).toEqual(['12'])
    expect(filterTables([table('12'), table('A1', false)], '', 'inactive').map((item) => item.label)).toEqual(['A1'])
  })

  it('previews bulk creation without hiding existing tables', () => {
    expect(bulkPreview(1, 5, [table('3'), table('4')])).toEqual({
      valid: true, newLabels: ['1', '2', '5'], existingLabels: ['3', '4'],
    })
  })

  it('rejects unsafe bulk ranges', () => {
    expect(bulkPreview(1, 101, [])).toMatchObject({ valid: false })
  })

  it('moves one id while preserving the complete sequence', () => {
    expect(moveId(['a', 'b', 'c'], 'b', -1)).toEqual(['b', 'a', 'c'])
    expect(moveId(['a', 'b', 'c'], 'a', -1)).toEqual(['a', 'b', 'c'])
  })
})
