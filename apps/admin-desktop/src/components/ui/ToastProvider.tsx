import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react'
import type { ReactNode } from 'react'
import { AlertTriangle, CheckCircle2, Info, X, XCircle } from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { cx } from '../../utils/cx'
import { upsertToast } from './toast.logic'
import './Toast.css'

export type ToastVariant = 'success' | 'info' | 'warning' | 'error'

interface ToastItem {
  id: string
  variant: ToastVariant
  message: string
  duration: number
}

interface ToastContextValue {
  push: (variant: ToastVariant, message: string, duration?: number, id?: string) => string
  dismiss: (id: string) => void
}

const ToastContext = createContext<ToastContextValue | null>(null)

const DEFAULT_DURATION: Record<ToastVariant, number> = {
  success: 4000,
  info: 4000,
  warning: 5000,
  error: 6000,
}

const VARIANT_ICON: Record<ToastVariant, LucideIcon> = {
  success: CheckCircle2,
  info: Info,
  warning: AlertTriangle,
  error: XCircle,
}

let nextId = 0

export function ToastProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<ToastItem[]>([])
  const timers = useRef(new Map<string, number>())

  const dismiss = useCallback((id: string) => {
    setToasts((current) => current.filter((toast) => toast.id !== id))
    const timerId = timers.current.get(id)
    if (timerId !== undefined) {
      window.clearTimeout(timerId)
      timers.current.delete(id)
    }
  }, [])

  const push = useCallback(
    (variant: ToastVariant, message: string, duration?: number, stableId?: string) => {
      const id = stableId ?? `toast-${++nextId}`
      const resolvedDuration = duration ?? DEFAULT_DURATION[variant]
      const existingTimer = timers.current.get(id)
      if (existingTimer !== undefined) window.clearTimeout(existingTimer)
      setToasts((current) => upsertToast(current, { id, variant, message, duration: resolvedDuration }))

      const timerId = window.setTimeout(() => dismiss(id), resolvedDuration)
      timers.current.set(id, timerId)
      return id
    },
    [dismiss],
  )

  useEffect(() => {
    const activeTimers = timers.current
    return () => {
      activeTimers.forEach((timerId) => window.clearTimeout(timerId))
    }
  }, [])

  return (
    <ToastContext.Provider value={{ push, dismiss }}>
      {children}
      <div className="toast-viewport" role="region" aria-label="Notificaciones">
        {toasts.map((toast) => {
          const Icon = VARIANT_ICON[toast.variant]
          return (
            <div
              key={toast.id}
              className={cx('toast', `toast--${toast.variant}`)}
              role={toast.variant === 'error' ? 'alert' : 'status'}
            >
              <Icon size={18} className="toast__icon" aria-hidden="true" />
              <span className="toast__message">{toast.message}</span>
              <button
                type="button"
                className="toast__close"
                aria-label="Cerrar notificación"
                onClick={() => dismiss(toast.id)}
              >
                <X size={14} aria-hidden="true" />
              </button>
            </div>
          )
        })}
      </div>
    </ToastContext.Provider>
  )
}

export function useToast() {
  const ctx = useContext(ToastContext)
  if (!ctx) throw new Error('useToast debe usarse dentro de <ToastProvider>')

  return {
    show: (options: { id?: string; variant: ToastVariant; message: string; duration?: number }) =>
      ctx.push(options.variant, options.message, options.duration, options.id),
    dismiss: ctx.dismiss,
    success: (message: string, duration?: number, id?: string) => ctx.push('success', message, duration, id),
    info: (message: string, duration?: number, id?: string) => ctx.push('info', message, duration, id),
    warning: (message: string, duration?: number, id?: string) => ctx.push('warning', message, duration, id),
    error: (message: string, duration?: number, id?: string) => ctx.push('error', message, duration, id),
  }
}
