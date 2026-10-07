import { describe, expect, it, vi } from 'vitest'
import { PollingLifecycle } from './pollingLifecycle'

describe('polling lifecycle', () => {
  it('does not start twice and can restart after StrictMode cleanup', () => {
    const lifecycle = new PollingLifecycle(vi.fn())
    expect(lifecycle.start()).toBe(true)
    expect(lifecycle.start()).toBe(false)
    lifecycle.stop()
    expect(lifecycle.start()).toBe(true)
  })

  it('cancels the timer and active request on unmount', () => {
    const cancelTimer = vi.fn()
    const abort = vi.fn()
    const lifecycle = new PollingLifecycle(cancelTimer)
    lifecycle.start()
    lifecycle.replaceTimer(42)
    lifecycle.replaceRequest({ abort })
    lifecycle.stop()

    expect(cancelTimer).toHaveBeenCalledWith(42)
    expect(abort).toHaveBeenCalledOnce()
    expect(lifecycle.active).toBe(false)
  })
})
