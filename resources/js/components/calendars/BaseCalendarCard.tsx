import { ReactNode, useState } from 'react'

/**
 * Pixel-art icon source per calendar key (code keys use underscores).
 */
const CALENDAR_ICON_SRCS: Record<string, string> = {
  gregorian: '/images/sd/sun.svg',
  jalali: '/images/sd/icon-tilestar.svg',
  hebrew: '/images/sd/icon-tome.svg',
  islamic: '/images/sd/icon-moon.svg',
  chinese: '/images/sd/icon-lantern.svg',
  japanese: '/images/sd/icon-torii.svg',
  coptic: '/images/sd/icon-cross-coptic.svg',
  ethiopian: '/images/sd/sprout.svg',
  egyptian: '/images/sd/icon-ankh.svg',
  french_republican: '/images/sd/icon-banner.svg',
  babylonian: '/images/sd/icon-ziggurat.svg',
  assyrian: '/images/sd/icon-ziggurat.svg',
  macedonian: '/images/sd/icon-pillar.svg',
  mayan: '/images/sd/icon-tablet.svg',
  julian: '/images/sd/icon-pillar.svg',
  julian_day: '/images/sd/icon-star.svg',
  jd: '/images/sd/icon-star.svg',
  modified_julian_day: '/images/sd/icon-star.svg',
  mjd: '/images/sd/icon-star.svg',
  unix: '/images/sd/icon-clock.svg',
  iso8601: '/images/sd/icon-calendar.svg',
}

interface CalendarIconProps {
  calendarKey: string
  size?: number
}

export function CalendarIcon({ calendarKey, size = 16 }: CalendarIconProps) {
  const src = CALENDAR_ICON_SRCS[calendarKey] ?? '/images/sd/icon-calendar.svg'
  return (
    <img
      src={src}
      width={size}
      height={size}
      className="sd-pixel shrink-0"
      aria-hidden="true"
    />
  )
}

interface BaseCalendarCardProps {
  title: string
  copyText?: string
  copyType: string
  disclaimer?: string
  className?: string
  icon?: ReactNode
  children: ReactNode
}

export function BaseCalendarCard({
  title,
  copyText,
  copyType,
  disclaimer,
  className = '',
  icon,
  children,
}: BaseCalendarCardProps) {
  const [copiedFeedback, setCopiedFeedback] = useState<string | null>(null)
  const [showDisclaimer, setShowDisclaimer] = useState(false)

  const copyToClipboard = async (text: string, type: string) => {
    try {
      await navigator.clipboard.writeText(text)
      setCopiedFeedback(type)
      setTimeout(() => setCopiedFeedback(null), 3000)
    } catch (err) {
      console.error('Failed to copy:', err)
    }
  }

  return (
    <div className={`sd-card p-2 flex flex-col ${className}`}>
      <div className="sd-card__title">
        <h3 className="sd-label flex items-center gap-2 flex-1 min-w-0 text-[color:var(--sd-ink)]">
          {icon}
          {title}
        </h3>
        <div className="flex items-center gap-1 shrink-0">
          {disclaimer && (
            <div className="relative">
              <button
                onMouseEnter={() => setShowDisclaimer(true)}
                onMouseLeave={() => setShowDisclaimer(false)}
                className="text-[color:var(--sd-ink-soft)] hover:text-[color:var(--sd-ink)]"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  strokeWidth={2}
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </button>
              {showDisclaimer && (
                <div className="sd-slot absolute bottom-full right-0 mb-2 w-64 p-2 z-10">
                  <p className="sd-small text-[color:var(--sd-cream)]">
                    {disclaimer}
                  </p>
                </div>
              )}
            </div>
          )}
          {copyText && (
            <button
              onClick={() => copyToClipboard(copyText, copyType)}
              title="Copy"
              className="text-[color:var(--sd-ink-soft)] hover:text-[color:var(--sd-ink)] transition-colors"
            >
              {copiedFeedback === copyType ? (
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
          )}
        </div>
      </div>
      <div className="flex-grow flex flex-col items-center justify-center text-center px-2 pt-1 pb-2 text-[color:var(--sd-ink)]">
        {children}
      </div>
    </div>
  )
}
