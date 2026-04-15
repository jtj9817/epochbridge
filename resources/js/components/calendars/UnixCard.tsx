import { BaseCalendarCard } from './BaseCalendarCard'
import { type NumericCalendarResult } from '@/types'

interface UnixCardProps {
  data: NumericCalendarResult
}

export function UnixCard({ data }: UnixCardProps) {
  return (
    <BaseCalendarCard
      title="Unix"
      copyText={data.full_date}
      copyType="unix"
      className="calendar-unix"
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
      <p className="text-2xl font-mono text-green-600 dark:text-green-400 tracking-wider">
        {data.value ?? 'N/A'}
      </p>
      <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-2 uppercase tracking-widest">
        seconds since epoch
      </p>
    </BaseCalendarCard>
  )
}
