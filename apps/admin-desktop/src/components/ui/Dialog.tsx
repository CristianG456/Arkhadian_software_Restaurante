import { useEffect, useRef } from 'react'
import type { MouseEvent, ReactNode } from 'react'
import { X } from 'lucide-react'
import { cx } from '../../utils/cx'
import { IconButton } from './IconButton'
import './Dialog.css'

export type DialogSize = 'sm' | 'md' | 'lg'

export interface DialogProps {
  open: boolean
  onClose: () => void
  title?: string
  description?: string
  children?: ReactNode
  footer?: ReactNode
  size?: DialogSize
  /** false for actions in progress that must not be dismissed accidentally
   * - suppresses Escape and outside-click, and hides the close button. */
  dismissible?: boolean
}

/**
 * Built on the native <dialog> element: showModal()/close() give us
 * top-layer rendering, focus trapping, and focus restoration on close for
 * free, instead of reimplementing them.
 */
export function Dialog({
  open,
  onClose,
  title,
  description,
  children,
  footer,
  size = 'md',
  dismissible = true,
}: DialogProps) {
  const ref = useRef<HTMLDialogElement>(null)

  useEffect(() => {
    const dialog = ref.current
    if (!dialog) return

    if (open && !dialog.open) {
      dialog.showModal()
    } else if (!open && dialog.open) {
      dialog.close()
    }
  }, [open])

  useEffect(() => {
    const dialog = ref.current
    if (!dialog) return

    function handleCancel(event: Event) {
      if (!dismissible) {
        event.preventDefault()
        return
      }
      onClose()
    }

    function handleClose() {
      onClose()
    }

    dialog.addEventListener('cancel', handleCancel)
    dialog.addEventListener('close', handleClose)
    return () => {
      dialog.removeEventListener('cancel', handleCancel)
      dialog.removeEventListener('close', handleClose)
    }
  }, [dismissible, onClose])

  function handleBackdropClick(event: MouseEvent<HTMLDialogElement>) {
    if (!dismissible || event.target !== ref.current) return
    const rect = ref.current.getBoundingClientRect()
    const insideContent =
      event.clientX >= rect.left &&
      event.clientX <= rect.right &&
      event.clientY >= rect.top &&
      event.clientY <= rect.bottom
    if (!insideContent) onClose()
  }

  return (
    <dialog
      ref={ref}
      className={cx('dialog', `dialog--${size}`)}
      aria-labelledby={title ? 'dialog-title' : undefined}
      onClick={handleBackdropClick}
    >
      {(title || dismissible) && (
        <header className="dialog__header">
          <div>
            {title && (
              <h2 id="dialog-title" className="dialog__title">
                {title}
              </h2>
            )}
            {description && <p className="dialog__description">{description}</p>}
          </div>
          {dismissible && (
            <IconButton
              icon={<X size={18} />}
              label="Cerrar"
              variant="ghost"
              onClick={onClose}
              tooltip={false}
            />
          )}
        </header>
      )}
      {children && <div className="dialog__body">{children}</div>}
      {footer && <footer className="dialog__footer">{footer}</footer>}
    </dialog>
  )
}
