import { env } from '../config/env'
import { ApiClientError } from './ApiClientError'
import type { ApiErrorBody } from './types'

interface RequestOptions {
  timeoutMs?: number
  signal?: AbortSignal
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE'
  body?: unknown
}

function isErrorEnvelope(body: unknown): body is ApiErrorBody {
  return (
    typeof body === 'object' &&
    body !== null &&
    (body as { success?: unknown }).success === false &&
    typeof (body as { error?: unknown }).error === 'object'
  )
}

/**
 * The only place in Admin Desktop allowed to call `fetch` against
 * Restaurant Core. Centralizes the base URL, timeout, JSON parsing and
 * error classification so components never touch any of that directly.
 */
export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const controller = new AbortController()
  const timeoutMs = options.timeoutMs ?? env.requestTimeoutMs
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs)

  if (options.signal) {
    options.signal.addEventListener('abort', () => controller.abort(), { once: true })
  }

  let response: Response
  try {
    const isFormData = options.body instanceof FormData
    const requestBody: BodyInit | undefined = options.body === undefined
      ? undefined
      : isFormData
        ? (options.body as FormData)
        : JSON.stringify(options.body)
    response = await fetch(`${env.coreBaseUrl}${path}`, {
      method: options.method ?? 'GET',
      headers: {
        Accept: 'application/json',
        ...(options.body === undefined || isFormData ? {} : { 'Content-Type': 'application/json' }),
      },
      body: requestBody,
      signal: controller.signal,
    })
  } catch (cause) {
    if (controller.signal.aborted) {
      throw new ApiClientError('timeout', `Timed out requesting ${path}`, { cause })
    }
    throw new ApiClientError('network', `Network error requesting ${path}`, { cause })
  } finally {
    clearTimeout(timeoutId)
  }

  let body: unknown
  try {
    body = await response.json()
  } catch (cause) {
    throw new ApiClientError('invalid_response', `Non-JSON response from ${path}`, {
      status: response.status,
      cause,
    })
  }

  if (!response.ok) {
    if (isErrorEnvelope(body)) {
      throw new ApiClientError('http', body.error.message, {
        status: response.status,
        code: body.error.code,
      })
    }

    throw new ApiClientError('http', `Request to ${path} failed with HTTP ${response.status}`, {
      status: response.status,
    })
  }

  return body as T
}
