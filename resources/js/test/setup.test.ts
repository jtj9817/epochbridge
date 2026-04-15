import { describe, it, expect } from 'vitest'

describe('Test Setup', () => {
  it('vitest is configured correctly', () => {
    expect(true).toBe(true)
  })

  it('localStorage mock works', () => {
    localStorage.setItem('test', 'value')
    expect(localStorage.getItem('test')).toBe('value')
  })

  it('crypto.randomUUID mock works', () => {
    const uuid = crypto.randomUUID()
    expect(uuid).toMatch(/^test-uuid-/)
  })
})
