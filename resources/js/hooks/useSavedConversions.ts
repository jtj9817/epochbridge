import { useState, useCallback, useEffect } from 'react'
import type { SavedConversion } from '@/types'
import {
  fetchSavedConversions,
  createSavedConversion,
  deleteSavedConversion,
  updateSavedConversionNote,
} from '@/lib/api'

interface UseSavedConversionsReturn {
  items: SavedConversion[]
  isLoading: boolean
  error: string | null
  save: (
    originalInput: string,
    conversionData: Record<string, unknown>,
    note?: string
  ) => Promise<SavedConversion | null>
  remove: (id: number) => Promise<void>
  updateNote: (id: number, note: string) => Promise<void>
  refresh: () => Promise<void>
}

export function useSavedConversions(
  isAuthenticated: boolean
): UseSavedConversionsReturn {
  const [items, setItems] = useState<SavedConversion[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const refresh = useCallback(async () => {
    if (!isAuthenticated) return

    setIsLoading(true)
    setError(null)

    try {
      const data = await fetchSavedConversions()
      setItems(data)
    } catch (err) {
      setError(
        err instanceof Error ? err.message : 'Failed to load saved conversions.'
      )
    } finally {
      setIsLoading(false)
    }
  }, [isAuthenticated])

  useEffect(() => {
    if (isAuthenticated) {
      refresh()
    }
  }, [isAuthenticated, refresh])

  const save = useCallback(
    async (
      originalInput: string,
      conversionData: Record<string, unknown>,
      note?: string
    ): Promise<SavedConversion | null> => {
      if (!isAuthenticated) return null

      setError(null)

      try {
        const result = await createSavedConversion({
          original_input: originalInput,
          conversion_data: conversionData,
          note,
        })

        await refresh()

        return result
      } catch (err) {
        setError(
          err instanceof Error ? err.message : 'Failed to save conversion.'
        )
        return null
      }
    },
    [isAuthenticated, refresh]
  )

  const remove = useCallback(
    async (id: number): Promise<void> => {
      setError(null)

      try {
        await deleteSavedConversion(id)
        setItems((prev) => prev.filter((item) => item.id !== id))
      } catch (err) {
        setError(
          err instanceof Error ? err.message : 'Failed to delete conversion.'
        )
      }
    },
    []
  )

  const updateNote = useCallback(
    async (id: number, note: string): Promise<void> => {
      setError(null)

      try {
        const trimmed = note.trim().slice(0, 2048)
        await updateSavedConversionNote(id, trimmed)
        setItems((prev) =>
          prev.map((item) =>
            item.id === id ? { ...item, note: trimmed } : item
          )
        )
      } catch (err) {
        setError(
          err instanceof Error ? err.message : 'Failed to update note.'
        )
      }
    },
    []
  )

  return {
    items,
    isLoading,
    error,
    save,
    remove,
    updateNote,
    refresh,
  }
}
