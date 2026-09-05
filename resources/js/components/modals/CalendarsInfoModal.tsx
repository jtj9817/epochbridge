import { useState, useEffect } from 'react'

interface CalendarsInfoModalProps {
  open: boolean
  onClose: () => void
}

interface CalendarInfo {
  emoji: string
  name: string
  category: string
  description: string
  details: string
  useCases: string
  technical: string
}

const CALENDARS: CalendarInfo[] = [
  {
    emoji: '📅',
    name: 'Gregorian Calendar',
    category: 'Modern Calendars',
    description:
      'The internationally accepted civil calendar system, introduced by Pope Gregory XIII in 1582.',
    details:
      'A solar calendar with 365 days in common years and 366 days in leap years. It is the most widely used calendar system worldwide for civil purposes.',
    useCases:
      'International business, civil administration, global communication, scientific documentation.',
    technical:
      '12 months, leap year every 4 years (except centurial years not divisible by 400).',
  },
  {
    emoji: '🌙',
    name: 'Jalali Calendar (Persian)',
    category: 'Modern Calendars',
    description:
      'The official calendar of Iran and Afghanistan, also known as the Solar Hijri calendar.',
    details:
      'One of the most astronomically accurate solar calendars, based on precise astronomical observations of the vernal equinox.',
    useCases:
      'Official use in Iran and Afghanistan, Persian cultural celebrations, Nowruz (Persian New Year).',
    technical:
      '12 months, first 6 months have 31 days, next 5 have 30 days, last month has 29 or 30 days in leap years.',
  },
  {
    emoji: '✡️',
    name: 'Hebrew Calendar',
    category: 'Modern Calendars',
    description:
      'A lunisolar calendar used for Jewish religious observances and Israel\'s official calendar.',
    details:
      'Combines lunar months with solar year adjustments through periodic leap months to keep holidays aligned with seasons.',
    useCases:
      'Jewish religious holidays, bar/bat mitzvahs, Hebrew date tracking, Israel civil calendar.',
    technical:
      '12 or 13 months (leap years), 353-355 or 383-385 days depending on year type.',
  },
  {
    emoji: '☪️',
    name: 'Islamic Calendar (Hijri)',
    category: 'Modern Calendars',
    description:
      'A purely lunar calendar used for Islamic religious purposes, starting from the Hijra (622 CE).',
    details:
      'Based entirely on lunar cycles with no solar adjustment, causing Islamic months to shift through seasons over time.',
    useCases:
      'Islamic religious observances, Ramadan, Eid celebrations, Hajj pilgrimage timing.',
    technical:
      '12 lunar months totaling 354 or 355 days, approximately 11 days shorter than solar year.',
  },
  {
    emoji: '🐉',
    name: 'Chinese Lunisolar Calendar',
    category: 'East Asian Calendars',
    description:
      'Traditional calendar system using astronomical calculations for lunations and solar terms.',
    details:
      'Features complex astronomical calculations including zodiac animals, heavenly stems, earthly branches, and 24 solar terms. Uses Beijing timezone (UTC+8) for all calculations.',
    useCases:
      'Chinese New Year, traditional festivals, zodiac year determination, agricultural planning, feng shui.',
    technical:
      'Lunisolar with leap months, 12 or 13 months per year. Uses Meeus algorithms with ΔT corrections for accuracy.',
  },
  {
    emoji: '🌸',
    name: 'Japanese Calendar',
    category: 'East Asian Calendars',
    description:
      'A Gregorian-based calendar system with traditional era names (nengō) based on Imperial reigns.',
    details:
      'Combines the Gregorian calendar structure with Japanese era names like Reiwa, Heisei, Shōwa, marking different Imperial periods.',
    useCases:
      'Official Japanese government documents, traditional ceremonies, cultural events, birth certificates.',
    technical:
      'Same structure as Gregorian but years counted from start of current era (e.g., Reiwa 7 instead of 2025).',
  },
  {
    emoji: '🏛️',
    name: 'Julian Calendar',
    category: 'Historical Calendars',
    description:
      'Predecessor to the Gregorian calendar, introduced by Julius Caesar in 45 BCE.',
    details:
      'A solar calendar with a simpler leap year rule than the Gregorian, causing gradual drift against astronomical seasons.',
    useCases:
      'Historical date research, Orthodox Christian Church dates, genealogical studies, classical scholarship.',
    technical:
      '365 days with leap year every 4 years without exception, causing 3-day drift every 400 years.',
  },
  {
    emoji: '🇫🇷',
    name: 'French Republican Calendar',
    category: 'Historical Calendars',
    description:
      'Revolutionary calendar used in France from 1793 to 1805, based on decimal system and reason.',
    details:
      'Featured 12 months of 30 days plus 5-6 complementary days. Implementation includes Paris mean solar offset (UTC+00:09:21) and supports proleptic extension beyond historical 1792-1805 range.',
    useCases:
      'French Revolution historical research, understanding revolutionary documents, historical education.',
    technical:
      '12 months of 30 days + 5 or 6 complementary days (sansculottides), 10-day weeks (décades).',
  },
  {
    emoji: '☦️',
    name: 'Coptic Calendar',
    category: 'Historical Calendars',
    description:
      'The liturgical calendar of the Coptic Orthodox Church, derived from ancient Egyptian calendar.',
    details:
      'Closely related to the Ethiopian calendar, with same structure but different epoch (Era of Martyrs starting 284 CE).',
    useCases:
      'Coptic Orthodox Church liturgy, Egyptian Christian festivals, historical Egyptian date conversion.',
    technical:
      '13 months: 12 months of 30 days each + 1 short month of 5 or 6 days.',
  },
  {
    emoji: '🇪🇹',
    name: 'Ethiopian Calendar',
    category: 'Historical Calendars',
    description:
      'The principal calendar in Ethiopia, approximately 7-8 years behind the Gregorian calendar.',
    details:
      'Still actively used as the official calendar of Ethiopia, with 13 months including a unique New Year celebration in September.',
    useCases:
      'Ethiopian civil and religious life, Orthodox Tewahedo Church, Ethiopian New Year (Enkutatash).',
    technical:
      '13 months: 12 months of 30 days + Pagumē (5 or 6 days). Year starts on Gregorian August 29 or 30.',
  },
  {
    emoji: '🏺',
    name: 'Ancient Egyptian Calendar',
    category: 'Ancient Calendars',
    description:
      'The civil calendar of ancient Egypt, one of the earliest solar calendars in human history.',
    details:
      'A purely solar calendar with 365 days per year, no leap year correction, causing gradual drift through seasons.',
    useCases:
      'Egyptology research, understanding ancient Egyptian texts, archaeological dating, historical studies.',
    technical:
      '12 months of 30 days + 5 epagomenal days (total 365 days), no leap years.',
  },
  {
    emoji: '🏺',
    name: 'Babylonian Calendar',
    category: 'Ancient Calendars',
    description:
      'Ancient Mesopotamian lunisolar calendar used in Babylon and throughout the ancient Near East.',
    details:
      'An influential calendar system that contributed to the development of the Hebrew and other Near Eastern calendars.',
    useCases:
      'Ancient Mesopotamian history, biblical chronology studies, archaeological research, cuneiform text interpretation.',
    technical:
      'Lunisolar with intercalary months added periodically to align lunar and solar cycles.',
  },
  {
    emoji: '📜',
    name: 'Assyrian Calendar',
    category: 'Ancient Calendars',
    description:
      'Calendar system used by the ancient Assyrian Empire in northern Mesopotamia.',
    details:
      'Related to the Babylonian calendar but with distinct month names and cultural traditions from Assyrian civilization.',
    useCases:
      'Assyrian historical research, ancient Near Eastern studies, Assyrian cultural heritage preservation.',
    technical:
      'Lunisolar calendar with Assyrian-specific month names and intercalation practices.',
  },
  {
    emoji: '🏛️',
    name: 'Ancient Macedonian Calendar',
    category: 'Ancient Calendars',
    description:
      'Calendar system used in ancient Macedon and spread throughout the Hellenistic world by Alexander the Great.',
    details:
      'A lunisolar calendar with distinct Macedonian month names, widely adopted in territories conquered by Alexander.',
    useCases:
      'Hellenistic period research, ancient Greek history, Alexander the Great\'s campaigns, classical studies.',
    technical:
      'Lunisolar with intercalary months, Macedonian month names distinct from Athenian calendar.',
  },
  {
    emoji: '🗿',
    name: 'Mayan Calendar',
    category: 'Ancient Calendars',
    description:
      'Complex Mesoamerican calendar system featuring multiple interlocking cycles including Long Count, Tzolk\'in, and Haab\'.',
    details:
      'One of the most sophisticated pre-Columbian calendar systems, featuring astronomical precision and multiple simultaneous cycles for different purposes.',
    useCases:
      'Mesoamerican archaeology, Maya cultural studies, understanding ancient Maya inscriptions, historical chronology.',
    technical:
      'Long Count (base-20 positional notation), Tzolk\'in (260-day ritual calendar), Haab\' (365-day civil calendar).',
  },
  {
    emoji: '🔢',
    name: 'ISO 8601',
    category: 'Technical Formats',
    description:
      'International standard for date and time representation, widely used in computing and data exchange.',
    details:
      'Provides unambiguous date-time notation (YYYY-MM-DDTHH:MM:SS) for international communication and data storage.',
    useCases:
      'APIs and web services, database storage, international data exchange, software development, log files.',
    technical:
      'Format: YYYY-MM-DDTHH:MM:SS±TZ. Supports time zones, durations, and intervals.',
  },
  {
    emoji: '⏰',
    name: 'Unix Timestamp',
    category: 'Technical Formats',
    description:
      'Number of seconds elapsed since January 1, 1970 00:00:00 UTC (Unix epoch).',
    details:
      'The fundamental time representation in Unix-like operating systems and many programming languages.',
    useCases:
      'System programming, database timestamps, file systems, cron jobs, server logs, API responses.',
    technical:
      'Signed integer (32-bit or 64-bit) counting seconds since 1970-01-01 00:00:00 UTC.',
  },
  {
    emoji: '🔭',
    name: 'Julian Day Number',
    category: 'Technical Formats',
    description:
      'Continuous count of days since January 1, 4713 BCE (proleptic Julian calendar), used in astronomy.',
    details:
      'The universal backbone for all calendar conversions in EpochBridge, providing astronomical accuracy. Note: Different from the Julian calendar (historical calendar system).',
    useCases:
      'Astronomical calculations, calendar conversions, satellite orbit computations, scientific research.',
    technical:
      'Integer day count starting from JD 0 = January 1, 4713 BCE at 12:00 UTC.',
  },
  {
    emoji: '🛰️',
    name: 'Modified Julian Day',
    category: 'Technical Formats',
    description:
      'Simplified Julian Day Number starting from midnight on November 17, 1858.',
    details:
      'Introduced to reduce the number of digits needed for modern dates while maintaining Julian Day compatibility.',
    useCases:
      'Satellite tracking, space missions, astronomical observations, radio astronomy, navigation systems.',
    technical:
      'MJD = JD - 2400000.5. Starts at midnight instead of noon, uses smaller numbers.',
  },
]

const CALENDAR_ICONS: Record<string, string> = {
  'Gregorian Calendar': 'icon-calendar',
  'Jalali Calendar (Persian)': 'sun',
  'Hebrew Calendar': 'icon-star',
  'Islamic Calendar (Hijri)': 'icon-moon',
  'Chinese Lunisolar Calendar': 'icon-lantern',
  'Japanese Calendar': 'icon-torii',
  'Julian Calendar': 'icon-pillar',
  'French Republican Calendar': 'icon-banner',
  'Coptic Calendar': 'icon-cross-coptic',
  'Ethiopian Calendar': 'flower-yellow',
  'Ancient Egyptian Calendar': 'icon-ankh',
  'Babylonian Calendar': 'icon-ziggurat',
  'Assyrian Calendar': 'icon-tablet',
  'Ancient Macedonian Calendar': 'icon-coin',
  'Mayan Calendar': 'icon-tilestar',
  'ISO 8601': 'icon-tome',
  'Unix Timestamp': 'icon-clock',
  'Julian Day Number': 'icon-scroll',
  'Modified Julian Day': 'icon-gear',
}

export function CalendarsInfoModal({ open, onClose }: CalendarsInfoModalProps) {
  const [visible, setVisible] = useState(false)

  useEffect(() => {
    if (open) setVisible(true)
  }, [open])

  useEffect(() => {
    if (!open && visible) {
      const timer = setTimeout(() => setVisible(false), 200)
      return () => clearTimeout(timer)
    }
  }, [open, visible])

  useEffect(() => {
    const handleKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && open) onClose()
    }
    document.addEventListener('keydown', handleKey)
    return () => document.removeEventListener('keydown', handleKey)
  }, [open, onClose])

  if (!visible) return null

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div className="fixed inset-0 sd-overlay" onClick={onClose} />
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4 pointer-events-none">
        <div
          className="sd-panel sd-pop pointer-events-auto flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden"
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
                Supported Calendar Systems
              </h3>
            </div>
            <button
              onClick={onClose}
              aria-label="Close modal"
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

          {/* Body */}
          <div className="flex-1 overflow-y-auto p-6">
            <p className="sd-small mb-6 text-[color:var(--sd-ink-soft)]">
              EpochBridge supports 19 calendar systems spanning modern,
              historical, ancient, and technical formats.
            </p>
            <div className="grid gap-4 md:grid-cols-2">
              {CALENDARS.map((cal) => (
                <div key={cal.name} className="sd-panel sd-panel--thin p-4">
                  <div className="mb-3 flex items-start gap-3">
                    <span className="sd-slot flex h-12 w-12 shrink-0 items-center justify-center">
                      <img
                        src={`/images/sd/${CALENDAR_ICONS[cal.name] ?? 'icon-calendar'}.svg`}
                        width={32}
                        height={32}
                        className="sd-pixel"
                        aria-hidden="true"
                      />
                    </span>
                    <div className="min-w-0 flex-1">
                      <h4 className="sd-small text-[color:var(--sd-ink)]">
                        {cal.name}
                      </h4>
                      <span className="sd-label mt-1 inline-block bg-[color:var(--sd-parch-dark)] px-2 py-0.5 text-[color:var(--sd-ink-soft)]">
                        {cal.category}
                      </span>
                    </div>
                  </div>
                  <p className="sd-small mb-2 text-[color:var(--sd-ink)]">
                    {cal.description}
                  </p>
                  <div className="space-y-2">
                    <p className="sd-data-sm text-[color:var(--sd-ink-soft)]">
                      <strong className="text-[color:var(--sd-ink)]">
                        Details:
                      </strong>{' '}
                      {cal.details}
                    </p>
                    <p className="sd-data-sm text-[color:var(--sd-ink-soft)]">
                      <strong className="text-[color:var(--sd-ink)]">
                        Use Cases:
                      </strong>{' '}
                      {cal.useCases}
                    </p>
                    <p className="sd-data-sm text-[color:var(--sd-ink-soft)]">
                      <strong className="text-[color:var(--sd-ink)]">
                        Technical:
                      </strong>{' '}
                      {cal.technical}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Footer */}
          <div className="flex items-center justify-between border-t-2 border-[color:var(--sd-parch-shadow)] px-6 py-4">
            <p className="sd-small text-[color:var(--sd-ink-soft)]">
              19 Supported Calendar Systems
            </p>
            <button
              onClick={onClose}
              type="button"
              className="sd-btn sd-btn--primary"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
