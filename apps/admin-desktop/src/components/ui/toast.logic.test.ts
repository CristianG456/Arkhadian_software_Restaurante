import { describe, expect, it } from 'vitest'
import { upsertToast } from './toast.logic'

describe('keyed toast deduplication', () => {
  it('keeps one active toast for one semantic incident id', () => {
    const first = { id: 'CORE_UNAVAILABLE', variant: 'error' as const, message: 'offline', duration: 6000 }
    const updated = { ...first, message: 'still offline' }
    const toasts = upsertToast(upsertToast([], first), updated)

    expect(toasts).toHaveLength(1)
    expect(toasts[0]?.message).toBe('still offline')
  })
})
