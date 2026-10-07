export interface KeyedToast {
  id: string
  variant: 'success' | 'info' | 'warning' | 'error'
  message: string
  duration: number
}

export function upsertToast(current: KeyedToast[], item: KeyedToast): KeyedToast[] {
  if (!current.some((toast) => toast.id === item.id)) return [...current, item]
  return current.map((toast) => (toast.id === item.id ? item : toast))
}
