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
  onSave?: () => void
}

export function CalendarResultsGrid({
  results,
  error,
  searchTerm,
  selectedTypes,
  onSave,
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

  const handleSave = useCallback(() => {
    onSave?.()
  }, [onSave])

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
        className="sd-panel sd-panel--thin my-8 p-4 max-w-2xl mx-auto"
        style={{
          background: 'color-mix(in srgb, var(--sd-red) 14%, var(--sd-parch))',
        }}
      >
        <p className="sd-label text-center text-[color:var(--sd-ink)]">
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
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <h2
          id="conversion-results-heading"
          className="sd-h3 text-[color:var(--sd-cream)]"
        >
          Conversion Results
        </h2>
        <div className="flex items-center gap-2">
          <button
            onClick={() => copyToClipboard(copyAllText, 'all')}
            className="sd-btn sd-btn--ghost sd-btn--sm"
          >
            <img
              src="/images/sd/icon-copy.svg"
              width={14}
              height={14}
              className="sd-pixel"
              aria-hidden="true"
            />
            {copiedFeedback === 'all' ? 'COPIED' : 'COPY ALL'}
          </button>
          <button
            onClick={handleSave}
            className="sd-btn sd-btn--primary sd-btn--sm"
          >
            <img
              src="/images/sd/icon-floppy.svg"
              width={14}
              height={14}
              className="sd-pixel"
              aria-hidden="true"
            />
            SAVE
          </button>
        </div>
      </div>

      <div
        id="results-grid"
        className="grid gap-3 md:grid-cols-2 lg:grid-cols-3"
      >
        {hasFilteredResults ? (
          Object.entries(filteredResults).map(([key, data]) =>
            renderCard(key, data)
          )
        ) : (
          <div className="md:col-span-2 lg:col-span-3">
            <div className="sd-panel sd-panel--thin px-4 py-6 text-center">
              <p className="sd-label text-[color:var(--sd-ink-soft)]">
                No calendars match your search criteria.
              </p>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
