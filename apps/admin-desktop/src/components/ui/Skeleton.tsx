import { cx } from '../../utils/cx'
import './Skeleton.css'

export interface SkeletonProps {
  width?: string | number
  height?: string | number
  className?: string
  circle?: boolean
}

/** A placeholder shape that reserves the exact space its content will
 * take, so loading never causes layout shift. */
export function Skeleton({ width, height = '1em', className, circle }: SkeletonProps) {
  return (
    <span
      className={cx('skeleton', circle && 'skeleton--circle', className)}
      style={{ width, height }}
      aria-hidden="true"
    />
  )
}
