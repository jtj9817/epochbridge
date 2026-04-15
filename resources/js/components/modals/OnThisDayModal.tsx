import { useState, useEffect } from 'react'
import type { OnThisDayData, OnThisDayEvent } from '@/types'

interface OnThisDayModalProps {
  open: boolean
  onClose: () => void
  data: OnThisDayData | null
  isLoading: boolean
  error: string | null
  dateLabel: string
}

interface SectionConfig {
  key: keyof OnThisDayData
  label: string
  borderLight: string
  borderDark: string
  bgLight: string
  bgDark: string
  icon: React.ReactNode
}

const SECTIONS: SectionConfig[] = [
  {
    key: 'selected',
    label: 'Selected',
    borderLight: 'border-l-amber-500',
    borderDark: 'dark:border-l-amber-400',
    bgLight: 'bg-amber-50',
    bgDark: 'dark:bg-amber-900/20',
    icon: (
      <svg className="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
      </svg>
    ),
  },
  {
    key: 'events',
    label: 'Events',
    borderLight: 'border-l-blue-500',
    borderDark: 'dark:border-l-blue-400',
    bgLight: 'bg-blue-50',
    bgDark: 'dark:bg-blue-900/20',
    icon: (
      <svg className="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
        <path
          fillRule="evenodd"
          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
          clipRule="evenodd"
        />
      </svg>
    ),
  },
  {
    key: 'births',
    label: 'Births',
    borderLight: 'border-l-emerald-500',
    borderDark: 'dark:border-l-emerald-400',
    bgLight: 'bg-emerald-50',
    bgDark: 'dark:bg-emerald-900/20',
    icon: (
      <svg className="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
        <path
          fillRule="evenodd"
          d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
          clipRule="evenodd"
        />
      </svg>
    ),
  },
  {
    key: 'deaths',
    label: 'Deaths',
    borderLight: 'border-l-zinc-400',
    borderDark: 'dark:border-l-zinc-500',
    bgLight: 'bg-zinc-100',
    bgDark: 'dark:bg-zinc-800/50',
    icon: (
      <svg className="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
        <path
          fillRule="evenodd"
          d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"
          clipRule="evenodd"
        />
      </svg>
    ),
  },
  {
    key: 'holidays',
    label: 'Holidays',
    borderLight: 'border-l-teal-500',
    borderDark: 'dark:border-l-teal-400',
    bgLight: 'bg-teal-50',
    bgDark: 'dark:bg-teal-900/20',
    icon: (
      <svg className="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
        <path d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zm7-10a1 1 0 01.707.293l.707.707.707-.707A1 1 0 0115.828 3l-.707.707.707.707a1 1 0 01-1.414 1.414L13.707 5.12l-.707.708a1 1 0 01-1.414-1.414l.707-.707-.707-.707A1 1 0 0112 2z" />
      </svg>
    ),
  },
]

const ITEMS_SHOWN = 3

function sanitizeUrl(url: string | null | undefined): string | null {
  if (typeof url !== 'string' || url === '') return null
  try {
    const parsed = new URL(url)
    if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') return null
    return url
  } catch {
    return null
  }
}

function SectionBlock({
  section,
  items,
}: {
  section: SectionConfig
  items: OnThisDayEvent[]
}) {
  const [expanded, setExpanded] = useState(false)
  const totalItems = items.length
  if (totalItems === 0) return null

  const visibleItems = expanded ? items : items.slice(0, ITEMS_SHOWN)
  const hasMore = totalItems > ITEMS_SHOWN

  return (
    <div>
      <div className="flex items-center justify-between mb-3 pb-2 border-b border-theme-200 dark:border-zinc-700">
        <div className="flex items-center gap-2">
          <span className="text-theme-500 dark:text-zinc-400">
            {section.icon}
          </span>
          <h4 className="text-sm font-bold text-theme-800 dark:text-zinc-100 uppercase tracking-widest">
            {section.label}
          </h4>
          <span className="text-xs text-theme-400 dark:text-zinc-500 uppercase tracking-wider">
            &mdash; {totalItems} {totalItems === 1 ? 'entry' : 'entries'}
          </span>
        </div>
        {hasMore && (
          <button
            type="button"
            onClick={() => setExpanded(!expanded)}
            className="flex items-center gap-1.5 px-3 py-1 text-xs uppercase tracking-wider font-medium text-theme-500 dark:text-zinc-400 border border-theme-300 dark:border-zinc-600 hover:bg-theme-100 dark:hover:bg-zinc-800 hover:text-theme-700 dark:hover:text-zinc-200 transition"
          >
            <svg
              className={`w-3 h-3 transition-transform ${expanded ? 'rotate-180' : ''}`}
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fillRule="evenodd"
                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                clipRule="evenodd"
              />
            </svg>
            <span>{expanded ? 'Show less' : `Show ${totalItems - ITEMS_SHOWN} more`}</span>
          </button>
        )}
      </div>
      <div className="space-y-2">
        {visibleItems.map((entry, index) => {
          const url = sanitizeUrl(entry.pages?.[0]?.content_urls?.desktop?.page)
          return (
            <div
              key={index}
              className={`flex bg-white dark:bg-zinc-800 border border-theme-200 dark:border-zinc-700 border-l-4 ${section.borderLight} ${section.borderDark} overflow-hidden`}
            >
              {entry.year != null && (
                <div
                  className={`flex-shrink-0 w-20 ${section.bgLight} ${section.bgDark} border-r border-theme-200 dark:border-zinc-700 flex items-center justify-center px-3 py-3`}
                >
                  <span className="text-sm font-bold text-zinc-700 dark:text-zinc-200 tabular-nums">
                    {entry.year}
                  </span>
                </div>
              )}
              <div className="flex-1 px-4 py-3 flex items-start justify-between gap-4">
                <p className="text-sm text-zinc-700 dark:text-zinc-200 leading-relaxed">
                  {entry.text}
                </p>
                {url && (
                  <a
                    href={url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex-shrink-0 flex items-center gap-1 px-2 py-1 text-xs uppercase tracking-wider font-medium text-theme-500 dark:text-zinc-400 border border-theme-300 dark:border-zinc-600 hover:bg-theme-100 dark:hover:bg-zinc-700 hover:text-theme-700 dark:hover:text-zinc-200 transition"
                  >
                    Read
                    <svg className="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                      <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                    </svg>
                  </a>
                )}
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}

export function OnThisDayModal({
  open,
  onClose,
  data,
  isLoading,
  error,
  dateLabel,
}: OnThisDayModalProps) {
  const [visible, setVisible] = useState(false)

  useEffect(() => {
    if (open) {
      setVisible(true)
    }
  }, [open])

  useEffect(() => {
    if (!open && visible) {
      const timer = setTimeout(() => setVisible(false), 200)
      return () => clearTimeout(timer)
    }
  }, [open, visible])

  useEffect(() => {
    const handleKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && open) {
        onClose()
      }
    }
    document.addEventListener('keydown', handleKey)
    return () => document.removeEventListener('keydown', handleKey)
  }, [open, onClose])

  if (!visible) return null

  const hasContent = data
    ? SECTIONS.some((s) => (data[s.key]?.length ?? 0) > 0)
    : false

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div
        className="fixed inset-0 bg-black/70 z-40"
        onClick={onClose}
      />
      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 relative z-50 pointer-events-none">
        <div
          className="relative inline-block align-bottom bg-white dark:bg-zinc-900 border-2 border-theme-300 dark:border-zinc-600 text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full pointer-events-auto"
          onClick={(e) => e.stopPropagation()}
        >
          {/* Header */}
          <div className="bg-theme-100 dark:bg-zinc-800 px-6 py-4 border-b-2 border-theme-300 dark:border-zinc-600 flex items-center justify-between gap-4">
            <div className="flex-1">
              <div className="flex items-center gap-3 flex-wrap">
                <h3 className="text-lg font-bold text-theme-900 dark:text-zinc-50 uppercase tracking-wider">
                  On This Day{dateLabel ? ` — ${dateLabel}` : ''}
                </h3>
              </div>
              <p className="text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-widest mt-1">
                English Wikipedia Highlights
              </p>
            </div>
            <button
              onClick={onClose}
              type="button"
              className="flex-shrink-0 p-2 text-theme-500 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-white hover:bg-theme-200/70 dark:hover:bg-zinc-700 transition border border-transparent hover:border-theme-300 dark:hover:border-zinc-600"
              aria-label="Close"
            >
              <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path
                  fillRule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clipRule="evenodd"
                />
              </svg>
            </button>
          </div>

          {/* Content */}
          <div className="bg-zinc-50 dark:bg-zinc-900 px-6 py-6 max-h-[70vh] overflow-y-auto">
            {isLoading && (
              <div className="flex items-center gap-3 text-theme-500 dark:text-zinc-400 py-8 justify-center">
                <svg
                  className="animate-spin h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    className="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    strokeWidth="4"
                  />
                  <path
                    className="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  />
                </svg>
                <span className="text-sm uppercase tracking-wider font-medium">
                  Loading historical highlights...
                </span>
              </div>
            )}

            {error && !isLoading && (
              <div className="border-2 border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-200">
                {error}
              </div>
            )}

            {!isLoading && !error && !hasContent && (
              <div className="border-2 border-theme-200 dark:border-zinc-600 bg-theme-50 dark:bg-zinc-800 px-4 py-3 text-sm text-theme-600 dark:text-zinc-300">
                No historical highlights available for this date yet.
              </div>
            )}

            {!isLoading && !error && hasContent && (
              <div className="space-y-6">
                {SECTIONS.map((section) => (
                  <SectionBlock
                    key={section.key}
                    section={section}
                    items={data?.[section.key] ?? []}
                  />
                ))}
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="bg-theme-100 dark:bg-zinc-800 px-6 py-4 border-t-2 border-theme-300 dark:border-zinc-600 flex justify-end">
            <button
              onClick={onClose}
              type="button"
              className="px-5 py-2 border-2 border-theme-300 dark:border-zinc-600 text-theme-700 dark:text-zinc-300 hover:bg-theme-200 dark:hover:bg-zinc-700 transition-colors text-sm font-medium uppercase tracking-wider"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
