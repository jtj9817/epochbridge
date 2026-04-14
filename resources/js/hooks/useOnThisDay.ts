import { useState, useCallback } from 'react'
import type { OnThisDayData } from '@/types'
import { fetchOnThisDay } from '@/lib/api'

interface UseOnThisDayReturn {
  data: OnThisDayData | null
  isLoading: boolean
  error: string | null
  dateLabel: string
  isOpen: boolean
  open: (gregorianDate: string) => void
  close: () => void
}

function formatDateLabel(dateStr: string): string {
  try {
    const date = new Date(dateStr + 'T00:00:00')
    return date.toLocaleDateString('en-US', {
      month: 'long',
      day: 'numeric',
    })
  } catch {
    return dateStr
  }
}

export function useOnThisDay(): UseOnThisDayReturn {
  const [data, setData] = useState<OnThisDayData | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [dateLabel, setDateLabel] = useState('')
  const [isOpen, setIsOpen] = useState(false)

  const open = useCallback(async (gregorianDate: string) => {
    setIsOpen(true)
    setIsLoading(true)
    setError(null)
    setData(null)
    setDateLabel(formatDateLabel(gregorianDate))

    try {
      const response = await fetchOnThisDay(gregorianDate)

      if (response.error) {
        setError(response.error)
      } else {
        setData(response.data ?? {})
      }
    } catch {
      setError('Failed to load historical events. Please try again.')
    } finally {
      setIsLoading(false)
    }
  }, [])

  const close = useCallback(() => {
    setIsOpen(false)
  }, [])

  return { data, isLoading, error, dateLabel, isOpen, open, close }
}
