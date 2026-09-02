export interface MenuRect { top: number; right: number; bottom: number; left: number }

export function actionMenuPosition(trigger: MenuRect, menu: { width: number; height: number }, viewport: { width: number; height: number }, align: 'start' | 'end') {
  const gap = 4
  const margin = 8
  const top = trigger.bottom + gap + menu.height <= viewport.height - margin
    ? trigger.bottom + gap
    : Math.max(margin, trigger.top - gap - menu.height)
  const preferredLeft = align === 'end' ? trigger.right - menu.width : trigger.left
  const left = Math.min(Math.max(margin, preferredLeft), Math.max(margin, viewport.width - menu.width - margin))
  return { top, left }
}
