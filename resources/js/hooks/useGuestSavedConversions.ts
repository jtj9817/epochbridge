import { useState, useCallback, useEffect } from 'react'
import type { GuestSavedConversion } from '@/types'
import {
  loadGuestConversions,
  addGuestConversion,
  deleteGuestConversion,
  updateGuestNote,
  clearGuestData,
  GUEST_SAVE_LIMIT,
} from '@/lib/guest-storage'

interface UseGuestSavedConversionsReturn {
  items: GuestSavedConversion[]
  saveLimitReached: boolean
  save: (
    originalInput: string,
    conversionData: Record<string, unknown>,
    note?: string
  ) => GuestSavedConversion | null
  remove: (id: number) => void
  updateNote: (id: number, note: string) => void
  clearAll: () => void
  getItemsForMigration: () => GuestSavedConversion[]
}

export function useGuestSavedConversions(
  isEnabled: boolean
): UseGuestSavedConversionsReturn {
  const [items, setItems] = useState<GuestSavedConversion[]>([])
  const [saveLimitReached, setSaveLimitReached] = useState(false)

  useEffect(() => {
    if (isEnabled) {
      setItems(loadGuestConversions())
    }
  }, [isEnabled])

  const save = useCallback(
    (
      originalInput: string,
      conversionData: Record<string, unknown>,
      note = ''
    ): GuestSavedConversion | null => {
      const result = addGuestConversion({
        original_input: originalInput,
        conversion_data: conversionData,
        note,
      })

      if (result) {
        setItems(loadGuestConversions())
        setSaveLimitReached(false)
      } else {
        const current = loadGuestConversions()
        setSaveLimitReached(current.length >= GUEST_SAVE_LIMIT)
      }

      return result
    },
    []
  )

  const remove = useCallback((id: number): void => {
    deleteGuestConversion(id)
    setItems(loadGuestConversions())
  }, [])

  const updateNote = useCallback((id: number, note: string): void => {
    updateGuestNote(id, note)
    setItems(loadGuestConversions())
  }, [])

  const clearAll = useCallback((): void => {
    clearGuestData()
    setItems([])
  }, [])

  const getItemsForMigration = useCallback((): GuestSavedConversion[] => {
    return loadGuestConversions()
  }, [])

  return {
    items,
    saveLimitReached,
    save,
    remove,
    updateNote,
    clearAll,
    getItemsForMigration,
  }
}
