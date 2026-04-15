import { BaseCalendarCard } from './BaseCalendarCard'
import { type CalendarResult, isStructuredResult, isNumericResult } from '@/types'

interface GenericCalendarCardProps {
  calendarKey: string
  data: CalendarResult
}

// Color mapping for different calendar types
const CALENDAR_COLORS: Record<string, { primary: string; secondary: string }> = {
  jalali: { primary: 'text-emerald-600 dark:text-emerald-400', secondary: 'text-emerald-500 dark:text-emerald-300' },
  hebrew: { primary: 'text-amber-600 dark:text-amber-400', secondary: 'text-amber-500 dark:text-amber-300' },
  islamic: { primary: 'text-teal-600 dark:text-teal-400', secondary: 'text-teal-500 dark:text-teal-300' },
  chinese: { primary: 'text-red-600 dark:text-red-400', secondary: 'text-red-500 dark:text-red-300' },
  japanese: { primary: 'text-rose-600 dark:text-rose-400', secondary: 'text-rose-500 dark:text-rose-300' },
  julian: { primary: 'text-sky-600 dark:text-sky-400', secondary: 'text-sky-500 dark:text-sky-300' },
  coptic: { primary: 'text-orange-600 dark:text-orange-400', secondary: 'text-orange-500 dark:text-orange-300' },
  ethiopian: { primary: 'text-lime-600 dark:text-lime-400', secondary: 'text-lime-500 dark:text-lime-300' },
  egyptian: { primary: 'text-yellow-600 dark:text-yellow-400', secondary: 'text-yellow-500 dark:text-yellow-300' },
  french_republican: { primary: 'text-cyan-600 dark:text-cyan-400', secondary: 'text-cyan-500 dark:text-cyan-300' },
  babylonian: { primary: 'text-amber-700 dark:text-amber-300', secondary: 'text-amber-600 dark:text-amber-200' },
  assyrian: { primary: 'text-stone-600 dark:text-stone-400', secondary: 'text-stone-500 dark:text-stone-300' },
  macedonian: { primary: 'text-fuchsia-600 dark:text-fuchsia-400', secondary: 'text-fuchsia-500 dark:text-fuchsia-300' },
  mayan: { primary: 'text-stone-700 dark:text-stone-300', secondary: 'text-stone-600 dark:text-stone-200' },
}

const DEFAULT_COLORS = { primary: 'text-zinc-600 dark:text-zinc-400', secondary: 'text-zinc-500 dark:text-zinc-300' }

export function GenericCalendarCard({ calendarKey, data }: GenericCalendarCardProps) {
  const colors = CALENDAR_COLORS[calendarKey] ?? DEFAULT_COLORS
  const disclaimer = 'disclaimer' in data ? (data as any).disclaimer : undefined

  // Handle Mayan calendar specially due to its unique structure
  if (calendarKey === 'mayan' && isStructuredResult(data)) {
    return (
      <BaseCalendarCard
        title={data.name}
        copyType={calendarKey}
        disclaimer={disclaimer}
        className={`calendar-${calendarKey}`}
        icon={
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
              d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
            />
          </svg>
        }
      >
        <div className="space-y-3 w-full">
          <div>
            <p className="text-xs text-stone-500 dark:text-stone-400 uppercase tracking-widest">
              Long Count
            </p>
            <p className={`text-xl font-mono ${colors.primary}`}>
              {data.long_count ?? 'N/A'}
            </p>
          </div>
          <div className="flex justify-between gap-4">
            <div className="flex-1">
              <p className="text-xs text-stone-500 dark:text-stone-400 uppercase tracking-widest">
                Tzolk'in
              </p>
              <p className={`text-base font-semibold ${colors.secondary}`}>
                {data.tzolkin ?? 'N/A'}
              </p>
            </div>
            <div className="flex-1">
              <p className="text-xs text-stone-500 dark:text-stone-400 uppercase tracking-widest">
                Haab'
              </p>
              <p className={`text-base font-semibold ${colors.secondary}`}>
                {data.haab ?? 'N/A'}
              </p>
            </div>
          </div>
        </div>
      </BaseCalendarCard>
    )
  }

  // Handle Chinese calendar with cycle information
  if (calendarKey === 'chinese' && isStructuredResult(data)) {
    return (
      <BaseCalendarCard
        title={data.name}
        copyText={data.full_date}
        copyType={calendarKey}
        disclaimer={disclaimer}
        className={`calendar-${calendarKey}`}
        icon={
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
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
        }
      >
        <div className={`text-xs font-medium ${colors.secondary} uppercase tracking-widest`}>
          {data.month_name ?? `Month ${data.month}`}
        </div>
        <div className={`text-5xl font-bold ${colors.primary} my-1`}>
          {data.day}
        </div>
        {data.stem_branch && (
          <div className={`text-sm ${colors.secondary} uppercase tracking-wider`}>
            {data.stem_branch}
          </div>
        )}
        <div className="text-xs font-mono text-zinc-500 dark:text-zinc-400 mt-2">
          {data.cycle ? `Cycle ${data.cycle}, Year ${data.cycle_year}` : data.year}
        </div>
      </BaseCalendarCard>
    )
  }

  // Handle Japanese calendar with era
  if (calendarKey === 'japanese' && isStructuredResult(data)) {
    return (
      <BaseCalendarCard
        title={data.name}
        copyText={data.full_date}
        copyType={calendarKey}
        disclaimer={disclaimer}
        className={`calendar-${calendarKey}`}
        icon={
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
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
        }
      >
        <div className={`text-xs font-medium ${colors.secondary} uppercase tracking-widest`}>
          {data.month_name ?? `Month ${data.month}`}
        </div>
        <div className={`text-5xl font-bold ${colors.primary} my-1`}>
          {data.day}
        </div>
        {data.era && (
          <div className={`text-sm ${colors.secondary} uppercase tracking-wider`}>
            {data.era} {data.year}
          </div>
        )}
        {data.day_of_week && (
          <div className="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
            {data.day_of_week}
          </div>
        )}
      </BaseCalendarCard>
    )
  }

  // Standard structured calendar card
  if (isStructuredResult(data)) {
    return (
      <BaseCalendarCard
        title={data.name}
        copyText={data.full_date}
        copyType={calendarKey}
        disclaimer={disclaimer}
        className={`calendar-${calendarKey}`}
        icon={
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
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
        }
      >
        <div className={`text-xs font-medium ${colors.secondary} uppercase tracking-widest`}>
          {data.month_name ?? `Month ${data.month}`}
        </div>
        <div className={`text-5xl font-bold ${colors.primary} my-1`}>
          {data.day}
        </div>
        {data.day_of_week && (
          <div className={`text-sm ${colors.secondary} uppercase tracking-wider`}>
            {data.day_of_week}
          </div>
        )}
        <div className="text-xs font-mono text-zinc-500 dark:text-zinc-400 mt-2">
          {data.year}
        </div>
      </BaseCalendarCard>
    )
  }

  // Numeric calendar card
  if (isNumericResult(data)) {
    return (
      <BaseCalendarCard
        title={data.name}
        copyText={data.full_date}
        copyType={calendarKey}
        className={`calendar-${calendarKey}`}
        icon={
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
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        }
      >
        <p className={`text-2xl font-mono ${colors.primary} tracking-wider`}>
          {data.value ?? 'N/A'}
        </p>
      </BaseCalendarCard>
    )
  }

  // Fallback for unknown types
  const displayValue =
    'full_date' in data ? String(data.full_date) : 'N/A'

  return (
    <BaseCalendarCard
      title={data.name}
      copyType={calendarKey}
      className={`calendar-${calendarKey}`}
    >
      <p className="text-lg font-mono text-zinc-600 dark:text-zinc-400">
        {displayValue}
      </p>
    </BaseCalendarCard>
  )
}
