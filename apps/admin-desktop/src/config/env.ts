/**
 * Every environment-dependent value the frontend needs, in one place.
 * Components must import from here — never read `import.meta.env` or
 * hardcode a URL/port directly.
 */

function readString(name: string, fallback: string): string {
  const value = import.meta.env[name]
  return typeof value === 'string' && value.length > 0 ? value : fallback
}

function readNumber(name: string, fallback: number): number {
  const raw = import.meta.env[name]
  const parsed = typeof raw === 'string' ? Number(raw) : NaN
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback
}

export const env = {
  /** Restaurant Core's REST API base URL. */
  coreBaseUrl: readString('VITE_CORE_BASE_URL', 'http://127.0.0.1:8000'),

  /** How often the status screen re-polls Core, in milliseconds. */
  statusPollIntervalMs: readNumber('VITE_STATUS_POLL_INTERVAL_MS', 5000),

  /** Maximum reconnect delay while Core remains unavailable. */
  statusPollMaxIntervalMs: readNumber('VITE_STATUS_POLL_MAX_INTERVAL_MS', 30000),

  /** Per-request timeout before a call to Core is treated as unreachable. */
  requestTimeoutMs: readNumber('VITE_CORE_REQUEST_TIMEOUT_MS', 3000),

  /** Admin Desktop's own version — sourced from package.json via Vite. */
  appVersion: String(__APP_VERSION__),
} as const
