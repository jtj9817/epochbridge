import type { GuestSavedConversion } from '@/types'

export const GUEST_SAVED_CONVERSIONS_KEY = 'savedConversions'
export const GUEST_COLLAPSED_STATES_KEY = 'conversionCollapsedStates'
export const GUEST_SAVE_LIMIT = 3

/**
 * Load guest conversions from localStorage.
 */
export function loadGuestConversions(): GuestSavedConversion[] {
  try {
    const stored = localStorage.getItem(GUEST_SAVED_CONVERSIONS_KEY)
    if (!stored) return []

    const parsed = JSON.parse(stored)
    if (!Array.isArray(parsed)) return []

    return parsed.filter(
      (item: unknown) =>
        typeof item === 'object' &&
        item !== null &&
        'original_input' in (item as Record<string, unknown>) &&
        'conversion_data' in (item as Record<string, unknown>)
    )
  } catch {
    return []
  }
}

/**
 * Save guest conversions to localStorage.
 */
export function saveGuestConversions(
  conversions: GuestSavedConversion[]
): void {
  localStorage.setItem(
    GUEST_SAVED_CONVERSIONS_KEY,
    JSON.stringify(conversions.slice(0, GUEST_SAVE_LIMIT))
  )
}

/**
 * Add a guest conversion (newest first, cap at GUEST_SAVE_LIMIT).
 * Returns the updated list, or null if the limit has been reached.
 */
export function addGuestConversion(
  conversion: Omit<GuestSavedConversion, 'id' | 'created_at'>
): GuestSavedConversion | null {
  const current = loadGuestConversions()

  if (current.length >= GUEST_SAVE_LIMIT) {
    return null
  }

  const duplicate = current.some(
    (item) => item.original_input === conversion.original_input
  )
  if (duplicate) {
    return null
  }

  const newItem: GuestSavedConversion = {
    ...conversion,
    id: Date.now(),
    created_at: new Date().toISOString(),
  }

  const updated = [newItem, ...current].slice(0, GUEST_SAVE_LIMIT)
  saveGuestConversions(updated)

  return newItem
}

/**
 * Delete a guest conversion by id.
 */
export function deleteGuestConversion(id: number): GuestSavedConversion[] {
  const current = loadGuestConversions()
  const updated = current.filter((item) => item.id !== id)
  saveGuestConversions(updated)
  return updated
}

/**
 * Update the note on a guest conversion.
 */
export function updateGuestNote(
  id: number,
  note: string
): GuestSavedConversion[] {
  const current = loadGuestConversions()
  const trimmed = note.trim().slice(0, 2048)
  const updated = current.map((item) =>
    item.id === id ? { ...item, note: trimmed } : item
  )
  saveGuestConversions(updated)
  return updated
}

/**
 * Load collapsed states for guest conversion items.
 */
export function loadCollapsedStates(): Record<number, boolean> {
  try {
    const stored = localStorage.getItem(GUEST_COLLAPSED_STATES_KEY)
    return stored ? JSON.parse(stored) : {}
  } catch {
    return {}
  }
}

/**
 * Persist collapsed states for guest conversion items.
 */
export function saveCollapsedStates(
  states: Record<number, boolean>
): void {
  localStorage.setItem(GUEST_COLLAPSED_STATES_KEY, JSON.stringify(states))
}

/**
 * Clear all guest data (called after successful migration).
 */
export function clearGuestData(): void {
  localStorage.removeItem(GUEST_SAVED_CONVERSIONS_KEY)
  localStorage.removeItem(GUEST_COLLAPSED_STATES_KEY)
}

/**
 * Check whether there is guest data awaiting migration.
 */
export function hasGuestData(): boolean {
  return loadGuestConversions().length > 0
}
