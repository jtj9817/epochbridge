import { useCallback, useMemo, useState } from 'react'
import { EditNoteModal } from '../modals/EditNoteModal'
import { CalendarIcon } from '../calendars/BaseCalendarCard'

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

  const primaryCalendarKey = useMemo(() => {
    const keys = Object.keys(conversionData)
    return keys.includes('gregorian') ? 'gregorian' : (keys[0] ?? 'gregorian')
  }, [conversionData])

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
        className="sd-panel sd-panel--thin"
        data-testid={`saved-conversion-${item.id}`}
      >
        <div className="flex items-start justify-between gap-3 p-3">
          <button
            type="button"
            onClick={() => setCollapsed((current) => !current)}
            className="flex items-center gap-3 flex-1 min-w-0 text-left"
            aria-expanded={!collapsed}
          >
            <span className="sd-slot w-12 h-12 flex items-center justify-center shrink-0">
              <CalendarIcon calendarKey={primaryCalendarKey} size={32} />
            </span>
            <span className="min-w-0">
              <span className="sd-label block text-[color:var(--sd-ink-soft)]">
                Input Date
              </span>
              <span className="sd-data-sm block truncate text-[color:var(--sd-ink)]">
                {item.original_input}
              </span>
              {createdAt && (
                <span className="sd-data-sm block text-[color:var(--sd-ink-soft)]">
                  Saved: {createdAt}
                </span>
              )}
              {hasLegacyIndicator && (
                <span className="inline-flex mt-1 px-2 py-0.5 sd-label border border-[color:var(--sd-gold-dark)] bg-[color:var(--sd-gold-fill)] text-[color:var(--sd-gold-text)]">
                  Legacy
                </span>
              )}
            </span>
          </button>

          <div className="flex flex-col gap-2 items-end">
            {item.note && (
              <button
                type="button"
                onClick={() => setNoteModalOpen(true)}
                className="sd-btn sd-btn--gold sd-btn--sm"
                title="Click to view/edit note"
              >
                <img
                  src="/images/sd/icon-scroll.svg"
                  width={14}
                  height={14}
                  className="sd-pixel"
                  aria-hidden="true"
                />
                <span>Note</span>
              </button>
            )}

            <div className="flex items-center gap-1">
              <button
                type="button"
                onClick={copyAll}
                className="p-2 text-[color:var(--sd-ink-soft)] hover:text-[color:var(--sd-ink)] transition-colors"
                title="Copy all conversions"
                aria-label="Copy all calendar conversions to clipboard"
              >
                {copiedField === 'all' ? (
                  <span className="sd-label text-[color:var(--sd-green)]">
                    OK
                  </span>
                ) : (
                  <img
                    src="/images/sd/icon-copy.svg"
                    width={16}
                    height={16}
                    className="sd-pixel"
                    aria-hidden="true"
                  />
                )}
              </button>

              <button
                type="button"
                onClick={() => setNoteModalOpen(true)}
                className={`p-2 transition-colors ${
                  item.note
                    ? 'text-[color:var(--sd-gold-text)] hover:text-[color:var(--sd-gold-dark)]'
                    : 'text-[color:var(--sd-ink-soft)] hover:text-[color:var(--sd-ink)]'
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
                className="p-2 text-[color:var(--sd-ink-soft)] hover:text-[color:var(--sd-red-dark)] transition-colors"
                title="Delete conversion"
                aria-label="Delete saved conversion"
              >
                <img
                  src="/images/sd/icon-trash.svg"
                  width={16}
                  height={16}
                  className="sd-pixel"
                  aria-hidden="true"
                />
              </button>

              <button
                type="button"
                onClick={() => setCollapsed((current) => !current)}
                className="cursor-pointer p-2 text-[color:var(--sd-ink)]"
                aria-expanded={!collapsed}
                aria-label="Toggle conversion details"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="3"
                  className={`w-5 h-5 transition-transform duration-200 ease-in-out ${
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
          <div className="px-3 pb-3">
            {conversionEntries.length > 0 ? (
              <div className="sd-slot overflow-x-auto">
                <table className="w-full">
                  <tbody className="divide-y divide-white/10">
                    {conversionEntries.map(([key, value]) => {
                      const name = getCalendarName(key, value)
                      const calendarValue = getCalendarValue(value)
                      const dayOfWeek = getDayOfWeek(value)

                      return (
                        <tr
                          key={key}
                          className="hover:bg-white/5 transition-colors"
                        >
                          <td className="px-3 py-2 w-1/3">
                            <span className="sd-label text-[color:var(--sd-gold-light)]">
                              {name}
                            </span>
                          </td>
                          <td className="px-3 py-2 w-1/3 sd-data-sm text-[color:var(--sd-cream)]">
                            {calendarValue}
                          </td>
                          <td className="px-3 py-2 w-1/3 sd-small text-[color:var(--sd-cream)] opacity-60">
                            {dayOfWeek}
                          </td>
                          <td className="px-3 py-2 w-auto text-right">
                            <button
                              type="button"
                              onClick={() => copyValue(calendarValue, key)}
                              className="p-1.5 text-[color:var(--sd-cream)] opacity-70 hover:opacity-100 transition-opacity"
                              title="Copy value"
                              aria-label="Copy calendar value to clipboard"
                            >
                              {copiedField === key ? (
                                <span className="sd-label text-[color:var(--sd-gold)]">
                                  OK
                                </span>
                              ) : (
                                <img
                                  src="/images/sd/icon-copy.svg"
                                  width={14}
                                  height={14}
                                  className="sd-pixel"
                                  aria-hidden="true"
                                />
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
              <div className="sd-slot p-4">
                <p className="sd-small text-center text-[color:var(--sd-cream)] opacity-60">
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
