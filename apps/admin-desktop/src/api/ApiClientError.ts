export type ApiErrorKind = 'network' | 'timeout' | 'http' | 'invalid_response'

/**
 * The one error type every api/* call can throw. Callers branch on `kind`
 * (and optionally `code` from the backend's error envelope) instead of
 * inspecting fetch/DOM exception internals — see docs/architecture.md
 * "Frontend error handling".
 */
export class ApiClientError extends Error {
  readonly kind: ApiErrorKind
  readonly status?: number
  readonly code?: string

  constructor(
    kind: ApiErrorKind,
    message: string,
    options?: { status?: number; code?: string; cause?: unknown },
  ) {
    super(message, { cause: options?.cause })
    this.name = 'ApiClientError'
    this.kind = kind
    this.status = options?.status
    this.code = options?.code
  }

  /** Safe to show directly in the UI — never leaks raw technical detail. */
  get userMessage(): string {
    switch (this.kind) {
      case 'network':
        return 'No se puede conectar con Restaurant Core.'
      case 'timeout':
        return 'Restaurant Core no respondió a tiempo.'
      case 'http':
        return this.message || 'Restaurant Core respondió con un error.'
      case 'invalid_response':
        return 'Restaurant Core envió una respuesta inesperada.'
    }
  }
}
