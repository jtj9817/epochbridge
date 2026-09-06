import { beforeEach, describe, expect, it } from 'vitest'
import {
  applySeasonalBackground,
  getSeason,
  getSeasonalBackground,
} from './seasonal-background'

beforeEach(() => {
  document.documentElement.removeAttribute('data-season')
  document.documentElement.removeAttribute('data-season-variant')
  document.documentElement.style.removeProperty('--sd-season-background')
})

describe('seasonal backgrounds', () => {
  it.each([
    ['winter', new Date(2026, 0, 15)],
    ['spring', new Date(2026, 2, 1)],
    ['summer', new Date(2026, 5, 1)],
    ['autumn', new Date(2026, 8, 1)],
    ['winter', new Date(2026, 11, 1)],
  ] as const)('maps %s to its meteorological season', (season, date) => {
    expect(getSeason(date)).toBe(season)
  })

  it('rotates between both backgrounds on consecutive days', () => {
    const first = getSeasonalBackground(new Date(2026, 3, 14))
    const second = getSeasonalBackground(new Date(2026, 3, 15))

    expect(first.season).toBe('spring')
    expect(second.season).toBe('spring')
    expect(first.variant).not.toBe(second.variant)
    expect(first.path).not.toBe(second.path)
  })

  it('applies the selected background as root presentation state', () => {
    applySeasonalBackground(new Date(2026, 8, 6))

    expect(document.documentElement.dataset.season).toBe('autumn')
    expect(document.documentElement.dataset.seasonVariant).toMatch(/^[12]$/)
    expect(
      document.documentElement.style.getPropertyValue('--sd-season-background')
    ).toMatch(/^url\("\/images\/sd\/backgrounds\/autumn-(clear|rain)\.webp"\)$/)
  })
})
