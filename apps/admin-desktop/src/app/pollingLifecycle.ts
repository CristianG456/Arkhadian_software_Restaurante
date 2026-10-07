interface Abortable {
  abort: () => void
}

export class PollingLifecycle {
  active = false
  private timer: number | null = null
  private request: Abortable | null = null
  private readonly cancelTimer: (id: number) => void

  constructor(cancelTimer: (id: number) => void) {
    this.cancelTimer = cancelTimer
  }

  start(): boolean {
    if (this.active) return false
    this.active = true
    return true
  }

  replaceTimer(timer: number | null): void {
    this.clearTimer()
    this.timer = timer
  }

  clearTimer(): void {
    if (this.timer !== null) this.cancelTimer(this.timer)
    this.timer = null
  }

  replaceRequest(request: Abortable): void {
    this.request?.abort()
    this.request = request
  }

  stop(): void {
    this.active = false
    this.clearTimer()
    this.request?.abort()
    this.request = null
  }
}
