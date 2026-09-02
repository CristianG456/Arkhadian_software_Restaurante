export function formatCop(value: string | null): string {
  if (value === null) return 'Sin precio'
  const [whole] = value.split('.')
  return `$${(whole ?? '0').replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`
}

export function normalizeMoneyInput(value: string): string {
  const digits = value.replace(/\D/g, '').replace(/^0+(?=\d)/, '')
  return digits || '0'
}

export function normalizeCatalogPrice(value: string): string {
  const compact = value.trim().replace(/^\$\s*/, '').replace(/\s/g, '')
  const decimal = compact.replace(',', '.')
  if (/^(0|[1-9]\d*)(\.\d{1,2})?$/.test(decimal)) return decimal
  return normalizeMoneyInput(compact)
}
