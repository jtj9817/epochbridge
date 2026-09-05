import { useState } from 'react'
import {
  type CalendarFilterType,
  CALENDAR_FILTER_TYPES,
  CALENDAR_FILTER_LABELS,
} from '@/types'

interface CalendarSearchFilterProps {
  searchTerm: string
  selectedTypes: CalendarFilterType[]
  onSearchChange: (value: string) => void
  onTypesChange: (types: CalendarFilterType[]) => void
}

export function CalendarSearchFilter({
  searchTerm,
  selectedTypes,
  onSearchChange,
  onTypesChange,
}: CalendarSearchFilterProps) {
  const [showFilters, setShowFilters] = useState(false)

  const handleTypeToggle = (type: CalendarFilterType) => {
    if (selectedTypes.includes(type)) {
      onTypesChange(selectedTypes.filter((t) => t !== type))
    } else {
      onTypesChange([...selectedTypes, type])
    }
  }

  return (
    <div className="my-8 max-w-4xl mx-auto">
      <div className="sd-panel sd-panel--thin p-4">
        <div className="relative">
          <img
            src="/images/sd/icon-search.svg"
            width={16}
            height={16}
            aria-hidden="true"
            className="sd-pixel absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
          />
          <input
            type="search"
            value={searchTerm}
            onChange={(e) => onSearchChange(e.target.value)}
            placeholder="Search calendars... (e.g., Jalali, Hebrew, Ancient)"
            className="sd-input pl-9"
          />
        </div>

        <div className="mt-3 flex flex-wrap items-center gap-2">
          <button
            type="button"
            onClick={() => setShowFilters(!showFilters)}
            className="sd-btn sd-btn--ghost sd-btn--sm"
          >
            {showFilters ? 'Hide Filters' : 'Show Filters'}
          </button>
        </div>

        {showFilters && (
          <div className="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2">
            {CALENDAR_FILTER_TYPES.map((type) => (
              <label
                key={type}
                className={`sd-tab cursor-pointer flex items-center justify-center gap-2 text-center ${
                  selectedTypes.includes(type) ? 'sd-tab--active' : ''
                }`}
              >
                <input
                  type="checkbox"
                  checked={selectedTypes.includes(type)}
                  onChange={() => handleTypeToggle(type)}
                  className="sr-only"
                />
                <span>{CALENDAR_FILTER_LABELS[type]}</span>
              </label>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
