import { describe, expect, it } from 'vitest'
import { actionMenuPosition } from './actionMenu.logic'

describe('action menu positioning', () => {
  it('opens above near the viewport bottom', () => {
    expect(actionMenuPosition({ top: 650, bottom: 690, left: 900, right: 940 }, { width: 200, height: 240 }, { width: 1200, height: 700 }, 'end')).toEqual({ top: 406, left: 740 })
  })

  it('stays inside the right edge', () => {
    expect(actionMenuPosition({ top: 20, bottom: 60, left: 1180, right: 1220 }, { width: 200, height: 100 }, { width: 1200, height: 700 }, 'start')).toEqual({ top: 64, left: 992 })
  })
})
