const ENCODING = '0123456789ABCDEFGHJKMNPQRSTVWXYZ'
export function createUlid(now = Date.now(), random = Math.random): string {
  let time = now; let head = ''
  for (let i = 0; i < 10; i += 1) { head = ENCODING[time % 32] + head; time = Math.floor(time / 32) }
  let tail = ''
  for (let i = 0; i < 16; i += 1) tail += ENCODING[Math.floor(random() * 32)]
  return head + tail
}
export const moneyMinor = (value: string) => { const [whole = '0', decimal = ''] = value.split('.'); return Number(whole) * 100 + Number((decimal + '00').slice(0, 2)) }
export const formatMoney = (minor: number) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(minor / 100)
