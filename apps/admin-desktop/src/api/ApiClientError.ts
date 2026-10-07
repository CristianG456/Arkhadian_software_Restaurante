export type ApiErrorKind = 'network' | 'timeout' | 'http' | 'invalid_response' | 'cancelled'
export type ApiFailureCategory =
  | 'CORE_UNAVAILABLE'
  | 'TIMEOUT'
  | 'API_ERROR'
  | 'VALIDATION_ERROR'
  | 'DATABASE_UNAVAILABLE'
  | 'INVALID_RESPONSE'
  | 'CANCELLED'

/**
 * The one error type every api call can throw. Transport failures,
 * backend/domain responses and cancelled requests remain distinguishable.
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
      case 'cancelled':
        return 'La solicitud fue cancelada.'
    }
  }

  get category(): ApiFailureCategory {
    if (this.kind === 'network') return 'CORE_UNAVAILABLE'
    if (this.kind === 'timeout') return 'TIMEOUT'
    if (this.kind === 'invalid_response') return 'INVALID_RESPONSE'
    if (this.kind === 'cancelled') return 'CANCELLED'
    if (this.code === 'DATABASE_UNAVAILABLE') return 'DATABASE_UNAVAILABLE'
    if (this.status === 422) return 'VALIDATION_ERROR'
    return 'API_ERROR'
  }
}

export function isInfrastructureError(error: unknown): boolean {
  if (!(error instanceof ApiClientError)) return false
  return ['CORE_UNAVAILABLE', 'TIMEOUT', 'DATABASE_UNAVAILABLE', 'CANCELLED'].includes(error.category)
}
