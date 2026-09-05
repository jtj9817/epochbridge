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
  accent: string
  labelColor: string
  icon: string
}

const SECTIONS: SectionConfig[] = [
  {
    key: 'selected',
    label: 'Selected',
    accent: 'var(--sd-gold)',
    labelColor: 'var(--sd-ink)',
    icon: 'icon-star',
  },
  {
    key: 'events',
    label: 'Events',
    accent: 'var(--sd-blue)',
    labelColor: 'var(--sd-cream)',
    icon: 'icon-clock',
  },
  {
    key: 'births',
    label: 'Births',
    accent: 'var(--sd-green)',
    labelColor: 'var(--sd-cream)',
    icon: 'sprout',
  },
  {
    key: 'deaths',
    label: 'Deaths',
    accent: 'var(--sd-wood-dark)',
    labelColor: 'var(--sd-cream)',
    icon: 'icon-moon',
  },
  {
    key: 'holidays',
    label: 'Holidays',
    accent: 'var(--sd-red)',
    labelColor: 'var(--sd-cream)',
    icon: 'flower-pink',
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
      <div
        className="mb-3 flex items-center justify-between gap-3 px-3 py-2"
        style={{ backgroundColor: section.accent }}
      >
        <div className="flex items-center gap-2">
          <img
            src={`/images/sd/${section.icon}.svg`}
            width={16}
            height={16}
            className="sd-pixel"
            aria-hidden="true"
          />
          <h4
            className="sd-label"
            style={{ color: section.labelColor }}
          >
            {section.label}
          </h4>
          <span
            className="sd-small"
            style={{ color: section.labelColor }}
          >
            &mdash; {totalItems} {totalItems === 1 ? 'entry' : 'entries'}
          </span>
        </div>
        {hasMore && (
          <button
            type="button"
            onClick={() => setExpanded(!expanded)}
            className="sd-btn sd-btn--ghost sd-btn--sm"
          >
            <svg
              className={`h-3 w-3 transition-transform ${expanded ? 'rotate-180' : ''}`}
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
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
            <div key={index} className="sd-panel sd-panel--thin flex overflow-hidden">
              {entry.year != null && (
                <div className="sd-slot flex w-20 shrink-0 items-center justify-center px-3 py-3">
                  <span className="sd-data-md tabular-nums text-[color:var(--sd-gold)]">
                    {entry.year}
                  </span>
                </div>
              )}
              <div className="flex flex-1 items-start justify-between gap-4 px-4 py-3">
                <p className="sd-small text-[color:var(--sd-ink)]">
                  {entry.text}
                </p>
                {url && (
                  <a
                    href={url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="sd-btn sd-btn--ghost sd-btn--sm shrink-0"
                  >
                    Read
                    <svg className="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
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
      <div className="fixed inset-0 sd-overlay" onClick={onClose} />
      <div className="relative z-50 flex items-center justify-center min-h-screen px-4 pt-4 pb-20 pointer-events-none">
        <div
          className="sd-panel sd-pop pointer-events-auto flex w-full max-w-4xl flex-col overflow-hidden"
          onClick={(e) => e.stopPropagation()}
        >
          {/* Header */}
          <div className="sd-panel__title">
            <div className="flex items-center gap-2">
              <img
                src="/images/sd/icon-scroll.svg"
                width={16}
                height={16}
                className="sd-pixel"
                aria-hidden="true"
              />
              <h3 className="sd-h3 text-[color:var(--sd-ink)]">
                On This Day{dateLabel ? ` — ${dateLabel}` : ''}
              </h3>
            </div>
            <div className="flex items-center gap-3">
              <span className="sd-data-sm hidden text-[color:var(--sd-ink-soft)] sm:inline">
                English Wikipedia Highlights
              </span>
              <button
                onClick={onClose}
                type="button"
                aria-label="Close"
                className="sd-btn sd-btn--ghost sd-btn--sm"
              >
                <img
                  src="/images/sd/icon-x.svg"
                  width={16}
                  height={16}
                  className="sd-pixel"
                  aria-hidden="true"
                />
              </button>
            </div>
          </div>

          {/* Content */}
          <div className="max-h-[70vh] flex-1 overflow-y-auto p-6">
            {isLoading && (
              <div className="flex items-center justify-center gap-3 py-8">
                <img
                  src="/images/sd/icon-clock.svg"
                  width={32}
                  height={32}
                  className="sd-pixel animate-spin"
                  aria-hidden="true"
                />
                <span className="sd-small text-[color:var(--sd-ink-soft)]">
                  Loading historical highlights...
                </span>
              </div>
            )}

            {error && !isLoading && (
              <div
                className="sd-panel sd-panel--thin p-4"
                style={{ borderImageSource: 'var(--sd-frame-panel-red)' }}
              >
                <p className="sd-small text-[color:var(--sd-red)]">{error}</p>
              </div>
            )}

            {!isLoading && !error && !hasContent && (
              <div className="sd-panel sd-panel--thin p-4">
                <p className="sd-small text-[color:var(--sd-ink-soft)]">
                  No historical highlights available for this date yet.
                </p>
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
          <div className="flex justify-end border-t-2 border-[color:var(--sd-parch-shadow)] px-6 py-4">
            <button onClick={onClose} type="button" className="sd-btn sd-btn--ghost">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
