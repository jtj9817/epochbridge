import { BaseCalendarCard } from './BaseCalendarCard'
import { type NumericCalendarResult } from '@/types'

interface ModifiedJulianDayCardProps {
  data: NumericCalendarResult
}

export function ModifiedJulianDayCard({ data }: ModifiedJulianDayCardProps) {
  return (
    <BaseCalendarCard
      title="Modified Julian Day"
      copyText={data.full_date}
      copyType="modified_julian_day"
      className="calendar-modified-julian-day"
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
            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
          />
        </svg>
      }
    >
      <p className="text-2xl font-mono text-violet-600 dark:text-violet-400 tracking-wider">
        {data.day_number ?? data.value ?? 'N/A'}
      </p>
      <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-2 uppercase tracking-widest">
        MJD = JD - 2,400,000.5
      </p>
    </BaseCalendarCard>
  )
}
