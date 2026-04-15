import { describe, it, expect, beforeEach, vi } from 'vitest'
import {
  GUEST_SAVED_CONVERSIONS_KEY,
  GUEST_COLLAPSED_STATES_KEY,
  GUEST_SAVE_LIMIT,
  loadGuestConversions,
  saveGuestConversions,
  addGuestConversion,
  deleteGuestConversion,
  updateGuestNote,
  loadCollapsedStates,
  saveCollapsedStates,
  clearGuestData,
  hasGuestData,
} from './guest-storage'
import type { GuestSavedConversion } from '@/types'

const mockConversionData = {
  gregorian: { name: 'Gregorian', full_date: '2024-01-15' },
  unix: { name: 'Unix', full_date: '1705276800', value: 1705276800 },
}

const createMockConversion = (
  originalInput: string,
  id?: number
): GuestSavedConversion => ({
  id: id ?? Date.now(),
  original_input: originalInput,
  conversion_data: mockConversionData,
  note: '',
  created_at: new Date().toISOString(),
})

beforeEach(() => {
  localStorage.clear()
  vi.clearAllMocks()
})

describe('guest-storage', () => {
  describe('constants', () => {
    it('has correct localStorage key for saved conversions', () => {
      expect(GUEST_SAVED_CONVERSIONS_KEY).toBe('savedConversions')
    })

    it('has correct localStorage key for collapsed states', () => {
      expect(GUEST_COLLAPSED_STATES_KEY).toBe('conversionCollapsedStates')
    })

    it('has correct guest save limit', () => {
      expect(GUEST_SAVE_LIMIT).toBe(3)
    })
  })

  describe('loadGuestConversions', () => {
    it('returns empty array when no data exists', () => {
      const result = loadGuestConversions()
      expect(result).toEqual([])
    })

    it('returns empty array for invalid JSON', () => {
      localStorage.setItem(GUEST_SAVED_CONVERSIONS_KEY, 'invalid json')
      const result = loadGuestConversions()
      expect(result).toEqual([])
    })

    it('returns empty array for non-array data', () => {
      localStorage.setItem(GUEST_SAVED_CONVERSIONS_KEY, '{"not": "array"}')
      const result = loadGuestConversions()
      expect(result).toEqual([])
    })

    it('filters out invalid items missing required fields', () => {
      const items = [
        { original_input: '2024-01-15', conversion_data: {} },
        { original_input: '2024-01-16' }, // missing conversion_data
        { conversion_data: {} }, // missing original_input
      ]
      localStorage.setItem(GUEST_SAVED_CONVERSIONS_KEY, JSON.stringify(items))
      const result = loadGuestConversions()
      expect(result).toHaveLength(1)
      expect(result[0].original_input).toBe('2024-01-15')
    })

    it('loads valid conversions correctly', () => {
      const items = [createMockConversion('2024-01-15', 1)]
      localStorage.setItem(GUEST_SAVED_CONVERSIONS_KEY, JSON.stringify(items))
      const result = loadGuestConversions()
      expect(result).toHaveLength(1)
      expect(result[0].original_input).toBe('2024-01-15')
    })
  })

  describe('saveGuestConversions', () => {
    it('saves conversions to localStorage', () => {
      const items = [createMockConversion('2024-01-15', 1)]
      saveGuestConversions(items)
      const stored = localStorage.getItem(GUEST_SAVED_CONVERSIONS_KEY)
      expect(stored).not.toBeNull()
      expect(JSON.parse(stored!)).toHaveLength(1)
    })

    it('caps at GUEST_SAVE_LIMIT items', () => {
      const items = [
        createMockConversion('2024-01-15', 1),
        createMockConversion('2024-01-16', 2),
        createMockConversion('2024-01-17', 3),
        createMockConversion('2024-01-18', 4),
      ]
      saveGuestConversions(items)
      const stored = localStorage.getItem(GUEST_SAVED_CONVERSIONS_KEY)
      expect(JSON.parse(stored!)).toHaveLength(GUEST_SAVE_LIMIT)
    })
  })

  describe('addGuestConversion', () => {
    it('adds a new conversion', () => {
      const result = addGuestConversion({
        original_input: '2024-01-15',
        conversion_data: mockConversionData,
        note: '',
      })
      expect(result).not.toBeNull()
      expect(result?.original_input).toBe('2024-01-15')
      expect(loadGuestConversions()).toHaveLength(1)
    })

    it('generates id and created_at', () => {
      const result = addGuestConversion({
        original_input: '2024-01-15',
        conversion_data: mockConversionData,
        note: '',
      })
      expect(result?.id).toBeDefined()
      expect(result?.created_at).toBeDefined()
    })

    it('returns null when limit reached', () => {
      // Fill to limit
      for (let i = 0; i < GUEST_SAVE_LIMIT; i++) {
        addGuestConversion({
          original_input: `2024-01-${15 + i}`,
          conversion_data: mockConversionData,
          note: '',
        })
      }
      // Try to add one more
      const result = addGuestConversion({
        original_input: '2024-02-01',
        conversion_data: mockConversionData,
        note: '',
      })
      expect(result).toBeNull()
    })

    it('returns null for duplicate original_input', () => {
      addGuestConversion({
        original_input: '2024-01-15',
        conversion_data: mockConversionData,
        note: '',
      })
      const result = addGuestConversion({
        original_input: '2024-01-15',
        conversion_data: mockConversionData,
        note: '',
      })
      expect(result).toBeNull()
    })

    it('adds new conversions at the beginning (newest first)', () => {
      addGuestConversion({
        original_input: '2024-01-15',
        conversion_data: mockConversionData,
        note: '',
      })
      addGuestConversion({
        original_input: '2024-01-16',
        conversion_data: mockConversionData,
        note: '',
      })
      const loaded = loadGuestConversions()
      expect(loaded[0].original_input).toBe('2024-01-16')
      expect(loaded[1].original_input).toBe('2024-01-15')
    })
  })

  describe('deleteGuestConversion', () => {
    it('removes a conversion by id', () => {
      const added = addGuestConversion({
        original_input: '2024-01-15',
        conversion_data: mockConversionData,
        note: '',
      })!
      expect(loadGuestConversions()).toHaveLength(1)
      deleteGuestConversion(added.id)
      expect(loadGuestConversions()).toHaveLength(0)
    })

    it('returns updated list', async () => {
      const first = addGuestConversion({
        original_input: '2024-01-15',
        conversion_data: mockConversionData,
        note: '',
      })!
      // Add small delay to ensure different Date.now() IDs
      await new Promise((r) => setTimeout(r, 2))
      addGuestConversion({
        original_input: '2024-01-16',
        conversion_data: mockConversionData,
        note: '',
      })
      const result = deleteGuestConversion(first.id)
      expect(result).toHaveLength(1)
      expect(result[0].original_input).toBe('2024-01-16')
    })
  })

  describe('updateGuestNote', () => {
    it('updates the note on a conversion', () => {
      const added = addGuestConversion({
        original_input: '2024-01-15',
        conversion_data: mockConversionData,
        note: '',
      })!
      updateGuestNote(added.id, 'Test note')
      const loaded = loadGuestConversions()
      expect(loaded[0].note).toBe('Test note')
    })

    it('trims whitespace from notes', () => {
      const added = addGuestConversion({
        original_input: '2024-01-15',
        conversion_data: mockConversionData,
        note: '',
      })!
      updateGuestNote(added.id, '  Test note  ')
      const loaded = loadGuestConversions()
      expect(loaded[0].note).toBe('Test note')
    })

    it('caps notes at 2048 characters', () => {
      const added = addGuestConversion({
        original_input: '2024-01-15',
        conversion_data: mockConversionData,
        note: '',
      })!
      const longNote = 'a'.repeat(3000)
      updateGuestNote(added.id, longNote)
      const loaded = loadGuestConversions()
      expect(loaded[0].note.length).toBe(2048)
    })
  })

  describe('loadCollapsedStates', () => {
    it('returns empty object when no data exists', () => {
      expect(loadCollapsedStates()).toEqual({})
    })

    it('returns empty object for invalid JSON', () => {
      localStorage.setItem(GUEST_COLLAPSED_STATES_KEY, 'invalid')
      expect(loadCollapsedStates()).toEqual({})
    })

    it('loads collapsed states correctly', () => {
      const states = { 1: true, 2: false }
      localStorage.setItem(GUEST_COLLAPSED_STATES_KEY, JSON.stringify(states))
      expect(loadCollapsedStates()).toEqual(states)
    })
  })

  describe('saveCollapsedStates', () => {
    it('saves collapsed states to localStorage', () => {
      const states = { 1: true, 2: false }
      saveCollapsedStates(states)
      const stored = localStorage.getItem(GUEST_COLLAPSED_STATES_KEY)
      expect(JSON.parse(stored!)).toEqual(states)
    })
  })

  describe('clearGuestData', () => {
    it('removes all guest data from localStorage', () => {
      addGuestConversion({
        original_input: '2024-01-15',
        conversion_data: mockConversionData,
        note: '',
      })
      saveCollapsedStates({ 1: true })
      expect(localStorage.getItem(GUEST_SAVED_CONVERSIONS_KEY)).not.toBeNull()
      expect(localStorage.getItem(GUEST_COLLAPSED_STATES_KEY)).not.toBeNull()

      clearGuestData()

      expect(localStorage.getItem(GUEST_SAVED_CONVERSIONS_KEY)).toBeNull()
      expect(localStorage.getItem(GUEST_COLLAPSED_STATES_KEY)).toBeNull()
    })
  })

  describe('hasGuestData', () => {
    it('returns false when no guest data exists', () => {
      expect(hasGuestData()).toBe(false)
    })

    it('returns true when guest conversions exist', () => {
      addGuestConversion({
        original_input: '2024-01-15',
        conversion_data: mockConversionData,
        note: '',
      })
      expect(hasGuestData()).toBe(true)
    })
  })
})
