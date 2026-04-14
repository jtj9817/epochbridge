import { useMemo, useState, useCallback } from 'react'
import {
  type ConversionResultMap,
  type CalendarFilterType,
  type CalendarResult,
  isErrorResult,
} from '@/types'
import { GregorianCard } from './GregorianCard'
import { UnixCard } from './UnixCard'
import { Iso8601Card } from './Iso8601Card'
import { JulianDayCard } from './JulianDayCard'
import { ModifiedJulianDayCard } from './ModifiedJulianDayCard'
import { GenericCalendarCard } from './GenericCalendarCard'

interface CalendarResultsGridProps {
  results: ConversionResultMap | null
  error: string
  searchTerm: string
  selectedTypes: CalendarFilterType[]
  dateInput: string
  isAuthenticated: boolean
}

export function CalendarResultsGrid({
  results,
  error,
  searchTerm,
  selectedTypes,
  dateInput,
  isAuthenticated,
}: CalendarResultsGridProps) {
  const [copiedFeedback, setCopiedFeedback] = useState<string | null>(null)

  const copyToClipboard = useCallback(async (text: string, type: string) => {
    try {
      await navigator.clipboard.writeText(text)
      setCopiedFeedback(type)
      setTimeout(() => setCopiedFeedback(null), 3000)
    } catch (err) {
      console.error('Failed to copy:', err)
    }
  }, [])

  // Filter results based on search term and selected types
  const filteredResults = useMemo(() => {
    if (!results) return {}

    const filtered: ConversionResultMap = {}
    const lowerSearchTerm = searchTerm.toLowerCase()

    for (const [key, data] of Object.entries(results)) {
      // Skip error results
      if (isErrorResult(data)) continue

      const name = data.name.toLowerCase()
      const tags = data.tags || []

      // Check search match
      const searchMatch = !searchTerm || name.includes(lowerSearchTerm)

      // Check type match
      const typeMatch =
        selectedTypes.length === 0 ||
        tags.some((tag) =>
          selectedTypes.includes(tag.toLowerCase() as CalendarFilterType)
        )

      if (searchMatch && typeMatch) {
        filtered[key] = data
      }
    }

    return filtered
  }, [results, searchTerm, selectedTypes])

  // Generate copy-all text
  const copyAllText = useMemo(() => {
    if (!filteredResults) return ''

    const lines: string[] = []
    for (const [, data] of Object.entries(filteredResults)) {
      if (isErrorResult(data)) continue
      const label = data.name
      const value = data.full_date
      lines.push(`${label}: ${value}`)
    }
    return lines.join('\n')
  }, [filteredResults])

  const handleSave = useCallback(async () => {
    if (isAuthenticated) {
      // For authenticated users, call the save endpoint
      try {
        const response = await fetch('/frontend/saved-conversions', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN':
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '',
          },
          body: JSON.stringify({
            original_input: dateInput,
            conversion_data: results,
          }),
        })

        if (response.ok) {
          // Show success toast (implement toast system later)
          console.log('Conversion saved!')
        }
      } catch (err) {
        console.error('Failed to save:', err)
      }
    } else {
      // Guest save - store in localStorage
      const savedConversions = JSON.parse(
        localStorage.getItem('savedConversions') || '[]'
      )

      if (savedConversions.length >= 3) {
        alert('Guest limit reached (3 max). Register for unlimited saves!')
        return
      }

      const newConversion = {
        id: Date.now(),
        original_input: dateInput,
        conversion_data: results,
        note: '',
        created_at: new Date().toISOString(),
      }

      savedConversions.unshift(newConversion)
      localStorage.setItem(
        'savedConversions',
        JSON.stringify(savedConversions.slice(0, 3))
      )
      console.log('Guest conversion saved!')
    }
  }, [isAuthenticated, dateInput, results])

  const renderCard = (key: string, data: CalendarResult) => {
    if (isErrorResult(data)) {
      return null
    }

    switch (key) {
      case 'gregorian':
        return <GregorianCard key={key} data={data as any} />
      case 'unix':
        return <UnixCard key={key} data={data as any} />
      case 'iso8601':
        return <Iso8601Card key={key} data={data as any} />
      case 'julian_day':
      case 'jd':
        return <JulianDayCard key={key} data={data as any} />
      case 'modified_julian_day':
      case 'mjd':
        return <ModifiedJulianDayCard key={key} data={data as any} />
      default:
        return <GenericCalendarCard key={key} calendarKey={key} data={data} />
    }
  }

  // Show error message if present
  if (error) {
    return (
      <div
        id="conversion-error"
        className="my-8 p-4 max-w-2xl mx-auto bg-red-50 dark:bg-red-900/20 border-2 border-red-300 dark:border-red-700"
      >
        <p className="text-red-800 dark:text-red-200 text-xs uppercase tracking-wider font-medium">
          {error}
        </p>
      </div>
    )
  }

  // Don't render anything if no results
  if (!results || Object.keys(results).length === 0) {
    return null
  }

  const hasFilteredResults = Object.keys(filteredResults).length > 0

  return (
    <div id="conversion-results-section" className="max-w-4xl mx-auto mt-12">
      <div className="flex items-center justify-between mb-4 pb-4 border-b border-theme-300 dark:border-zinc-700">
        <h2
          id="conversion-results-heading"
          className="text-sm font-bold text-theme-900 dark:text-theme-50 uppercase tracking-widest"
        >
          Conversion Results
        </h2>
        <div className="flex items-center gap-4">
          <button
            onClick={() => copyToClipboard(copyAllText, 'all')}
            className={`text-xs font-medium uppercase tracking-wider ${
              copiedFeedback === 'all'
                ? 'text-green-600 dark:text-green-400'
                : 'text-theme-500 dark:text-theme-100 hover:text-theme-900 dark:hover:text-white'
            }`}
          >
            {copiedFeedback === 'all' ? 'COPIED' : 'COPY ALL'}
          </button>
          <button
            onClick={handleSave}
            className="text-xs font-medium text-theme-500 dark:text-theme-100 hover:text-theme-900 dark:hover:text-white uppercase tracking-wider"
          >
            SAVE
          </button>
        </div>
      </div>

      <div
        id="results-grid"
        className="grid-exposed border border-theme-300 dark:border-zinc-700"
      >
        <div className="grid md:grid-cols-2 lg:grid-cols-3">
          {hasFilteredResults ? (
            Object.entries(filteredResults).map(([key, data]) => (
              <div key={key} className="grid-cell">
                {renderCard(key, data)}
              </div>
            ))
          ) : (
            <div className="md:col-span-2 lg:col-span-3 text-center py-8 text-theme-500 dark:text-theme-100 text-xs uppercase tracking-wider">
              No calendars match your search criteria.
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
