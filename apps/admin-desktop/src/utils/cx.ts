/** Joins truthy class names. A one-purpose stand-in for a classnames dependency. */
export function cx(...classes: Array<string | false | null | undefined>): string {
  return classes.filter(Boolean).join(' ')
}
