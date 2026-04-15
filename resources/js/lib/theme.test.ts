import { beforeEach, describe, expect, it, vi } from 'vitest'
import { applyTheme } from './theme'

function mockMatchMedia(matches: boolean): void {
  Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: vi.fn().mockImplementation(() => ({
      matches,
      media: '(prefers-color-scheme: dark)',
      onchange: null,
      addListener: vi.fn(),
      removeListener: vi.fn(),
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    })),
  })
}

beforeEach(() => {
  document.documentElement.className = ''
  localStorage.clear()
  mockMatchMedia(false)
})

describe('theme route handling', () => {
  it('forces dark mode on the landing route', () => {
    localStorage.setItem('appearance', 'light')

    applyTheme('/')

    expect(document.documentElement.classList.contains('dark')).toBe(true)
  })

  it('forces dark mode on public auth routes', () => {
    localStorage.setItem('appearance', 'light')

    applyTheme('/login')

    expect(document.documentElement.classList.contains('dark')).toBe(true)
  })

  it('uses explicit light preference on app routes', () => {
    localStorage.setItem('appearance', 'light')

    applyTheme('/dashboard')

    expect(document.documentElement.classList.contains('dark')).toBe(false)
  })

  it('uses explicit dark preference on app routes', () => {
    localStorage.setItem('appearance', 'dark')

    applyTheme('/dashboard')

    expect(document.documentElement.classList.contains('dark')).toBe(true)
  })

  it('uses system preference on app routes when appearance is system', () => {
    localStorage.setItem('appearance', 'system')
    mockMatchMedia(true)

    applyTheme('/settings/profile')

    expect(document.documentElement.classList.contains('dark')).toBe(true)
  })
})

