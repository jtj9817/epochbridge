import { useCallback, useMemo, useState } from 'react'
import { EditNoteModal } from '../modals/EditNoteModal'

interface ConversionItemCommon {
  id: number
  original_input: string
  note: string
  conversion_data: Record<string, unknown>
  created_at: string | null
  isGuest: boolean
  uses_legacy_fallback?: boolean
}

interface SavedConversionItemProps {
  item: ConversionItemCommon
  onDelete: (id: number) => void
  onUpdateNote: (id: number, note: string) => void
}

type CalendarEntryValue =
  | string
  | number
  | null
  | undefined
  | {
      name?: string
      full_date?: string
      value?: string | number
      timestamp?: string | number
      day_number?: string | number
      long_count?: string | number
      day_of_week?: string
      error?: string
    }

const CALENDAR_NAME_OVERRIDES: Record<string, string> = {
  unix: 'Unix Timestamp',
  julian_day: 'Julian Day Number',
  'julian-day': 'Julian Day Number',
  modified_julian_day: 'Modified Julian Day',
  'modified-julian-day': 'Modified Julian Day',
}

function formatCalendarName(key: string): string {
  return (
    CALENDAR_NAME_OVERRIDES[key] ??
    key
      .replace(/[_-]/g, ' ')
      .replace(/\b\w/g, (letter) => letter.toUpperCase())
  )
}

function getCalendarName(key: string, value: CalendarEntryValue): string {
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    if (typeof value.name === 'string' && value.name.trim() !== '') {
      return value.name
    }
  }

  return formatCalendarName(key)
}

function getCalendarValue(value: CalendarEntryValue): string {
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    if (typeof value.full_date === 'string' && value.full_date !== '') {
      return value.full_date
    }
    if (
      typeof value.value === 'string' ||
      typeof value.value === 'number'
    ) {
      return String(value.value)
    }
    if (
      typeof value.timestamp === 'string' ||
      typeof value.timestamp === 'number'
    ) {
      return String(value.timestamp)
    }
    if (
      typeof value.day_number === 'string' ||
      typeof value.day_number === 'number'
    ) {
      return String(value.day_number)
    }
    if (
      typeof value.long_count === 'string' ||
      typeof value.long_count === 'number'
    ) {
      return String(value.long_count)
    }

    return 'N/A'
  }

  if (typeof value === 'string' || typeof value === 'number') {
    return String(value)
  }

  return 'N/A'
}

function getDayOfWeek(value: CalendarEntryValue): string {
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    return typeof value.day_of_week === 'string' ? value.day_of_week : ''
  }

  return ''
}

export function SavedConversionItem({
  item,
  onDelete,
  onUpdateNote,
}: SavedConversionItemProps) {
  const [collapsed, setCollapsed] = useState(true)
  const [noteModalOpen, setNoteModalOpen] = useState(false)
  const [copiedField, setCopiedField] = useState<string | null>(null)

  const conversionData = item.conversion_data as Record<string, CalendarEntryValue>

  const conversionEntries = useMemo(
    () =>
      Object.entries(conversionData).filter(([, value]) => {
        if (value && typeof value === 'object' && !Array.isArray(value)) {
          return !('error' in value)
        }

        return true
      }),
    [conversionData]
  )

  const copyValue = useCallback(async (text: string, field: string) => {
    try {
      await navigator.clipboard.writeText(text)
      setCopiedField(field)
      setTimeout(() => setCopiedField(null), 3000)
    } catch (err) {
      console.error('Failed to copy:', err)
    }
  }, [])

  const copyAll = useCallback(async () => {
    const text = conversionEntries
      .map(([key, value]) => {
        const name = getCalendarName(key, value)
        const calendarValue = getCalendarValue(value)
        const dayOfWeek = getDayOfWeek(value)

        return dayOfWeek
          ? `${name}: ${calendarValue} (${dayOfWeek})`
          : `${name}: ${calendarValue}`
      })
      .join('\n')

    await copyValue(text, 'all')
  }, [conversionEntries, copyValue])

  const createdAt = item.created_at
    ? new Date(item.created_at).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
      })
    : ''

  const hasLegacyIndicator =
    !item.isGuest && 'uses_legacy_fallback' in item && item.uses_legacy_fallback

  return (
    <>
      <div
        className="bg-white dark:bg-zinc-900 border-2 border-theme-300 dark:border-zinc-700 overflow-hidden"
        data-testid={`saved-conversion-${item.id}`}
      >
        <div className="bg-[#50493E] px-4 py-3 border-b border-theme-300 dark:border-zinc-700 flex items-start justify-between gap-4">
          <button
            type="button"
            onClick={() => setCollapsed((current) => !current)}
            className="flex-1 min-w-0 text-left"
            aria-expanded={!collapsed}
          >
            <p className="text-xs font-bold uppercase tracking-widest text-white/70">
              Input Date
            </p>
            <p className="mt-1 font-semibold text-white truncate font-mono">
              {item.original_input}
            </p>
            {createdAt && (
              <p className="mt-1 text-xs text-white/60 uppercase tracking-wider">
                Saved: {createdAt}
              </p>
            )}
            {hasLegacyIndicator && (
              <span className="inline-flex mt-2 px-2 py-0.5 text-[10px] uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/30">
                Legacy
              </span>
            )}
          </button>

          <div className="flex flex-col gap-2 items-end">
            {item.note && (
              <button
                type="button"
                onClick={() => setNoteModalOpen(true)}
                className="flex items-center gap-1.5 bg-yellow-500/20 px-3 py-1 border border-yellow-500/30 hover:bg-yellow-500/30"
                title="Click to view/edit note"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-4 w-4 text-yellow-400 flex-shrink-0"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  strokeWidth="2"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"
                  />
                </svg>
                <span className="text-xs text-yellow-300 font-medium">Note</span>
              </button>
            )}

            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={copyAll}
                className="text-blue-400 hover:text-blue-300 p-2 hover:bg-blue-900/20"
                title="Copy all conversions"
                aria-label="Copy all calendar conversions to clipboard"
              >
                {copiedField === 'all' ? (
                  <span className="text-xs uppercase tracking-wider text-green-400">
                    OK
                  </span>
                ) : (
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth="2"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                    />
                  </svg>
                )}
              </button>

              <button
                type="button"
                onClick={() => setNoteModalOpen(true)}
                className={`p-2 ${
                  item.note
                    ? 'text-yellow-400 hover:text-yellow-300 hover:bg-yellow-900/20'
                    : 'text-gray-400 hover:text-gray-300 hover:bg-gray-900/20'
                }`}
                title={item.note ? 'Edit note' : 'Add note'}
                aria-label={item.note ? 'Edit conversion note' : 'Add conversion note'}
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  strokeWidth="2"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                  />
                </svg>
              </button>

              <button
                type="button"
                onClick={() => onDelete(item.id)}
                className="text-red-400 hover:text-red-300 p-2 hover:bg-red-900/20"
                title="Delete conversion"
                aria-label="Delete saved conversion"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  strokeWidth="2"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                  />
                </svg>
              </button>

              <button
                type="button"
                onClick={() => setCollapsed((current) => !current)}
                className="cursor-pointer p-2"
                aria-expanded={!collapsed}
                aria-label="Toggle conversion details"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="3"
                  className={`w-5 h-5 transition-transform duration-200 ease-in-out text-white/80 ${
                    collapsed ? '' : 'rotate-180'
                  }`}
                  aria-hidden="true"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                  />
                </svg>
              </button>
            </div>
          </div>
        </div>

        {!collapsed && (
          <div className="border-t border-theme-300 dark:border-zinc-700">
            {conversionEntries.length > 0 ? (
              <div className="overflow-x-auto bg-[#13100B]">
                <table className="w-full">
                  <tbody className="divide-y divide-[#50493E]">
                    {conversionEntries.map(([key, value]) => {
                      const name = getCalendarName(key, value)
                      const calendarValue = getCalendarValue(value)
                      const dayOfWeek = getDayOfWeek(value)

                      return (
                        <tr
                          key={key}
                          className="hover:bg-[#50493E]/30 transition-colors"
                        >
                          <td className="px-4 py-2 w-1/3">
                            <span className="font-bold text-[#F5D2A5]">
                              {name}
                            </span>
                          </td>
                          <td className="px-4 py-2 w-1/3 text-[#CDB586]">
                            {calendarValue}
                          </td>
                          <td className="px-4 py-2 w-1/3 text-sm text-[#7E7254]">
                            {dayOfWeek}
                          </td>
                          <td className="px-4 py-2 w-auto text-right">
                            <button
                              type="button"
                              onClick={() => copyValue(calendarValue, key)}
                              className="text-theme-400 hover:text-theme-600 dark:text-theme-500 dark:hover:text-theme-300 transition-colors p-1.5 rounded hover:bg-theme-100 dark:hover:bg-theme-700/30"
                              title="Copy value"
                              aria-label="Copy calendar value to clipboard"
                            >
                              {copiedField === key ? (
                                <span className="text-xs uppercase tracking-wider text-green-400">
                                  OK
                                </span>
                              ) : (
                                <svg
                                  xmlns="http://www.w3.org/2000/svg"
                                  className="h-4 w-4"
                                  fill="none"
                                  viewBox="0 0 24 24"
                                  stroke="currentColor"
                                  strokeWidth="2"
                                >
                                  <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                  />
                                </svg>
                              )}
                            </button>
                          </td>
                        </tr>
                      )
                    })}
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="p-4 bg-[#13100B]">
                <p className="text-center text-[#7E7254]">
                  No conversion data available
                </p>
              </div>
            )}
          </div>
        )}
      </div>

      <EditNoteModal
        open={noteModalOpen}
        initialValue={item.note}
        onSave={(note) => {
          onUpdateNote(item.id, note)
          setNoteModalOpen(false)
        }}
        onClose={() => setNoteModalOpen(false)}
      />
    </>
  )
}

