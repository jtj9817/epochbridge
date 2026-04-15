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
      <div className="relative">
        <input
          type="search"
          value={searchTerm}
          onChange={(e) => onSearchChange(e.target.value)}
          placeholder="Search calendars... (e.g., Jalali, Hebrew, Ancient)"
          className="w-full pl-10 pr-4 py-3 border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
        />
        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg
            className="h-5 w-5 text-gray-400"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
        </div>
      </div>

      <div className="mt-4">
        <button
          type="button"
          onClick={() => setShowFilters(!showFilters)}
          className="text-sm text-theme-500 dark:text-theme-100 hover:underline"
        >
          {showFilters ? 'Hide Filters' : 'Show Filters'}
        </button>

        {showFilters && (
          <div className="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
            {CALENDAR_FILTER_TYPES.map((type) => (
              <label
                key={type}
                className="flex items-center space-x-2 text-sm text-theme-900 dark:text-theme-50"
              >
                <input
                  type="checkbox"
                  checked={selectedTypes.includes(type)}
                  onChange={() => handleTypeToggle(type)}
                  className="rounded border-theme-300 text-theme-300 focus:ring-theme-300"
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
