import { BaseCalendarCard } from './BaseCalendarCard'
import { type NumericCalendarResult } from '@/types'

interface Iso8601CardProps {
  data: NumericCalendarResult
}

export function Iso8601Card({ data }: Iso8601CardProps) {
  return (
    <BaseCalendarCard
      title="ISO 8601"
      copyText={data.full_date}
      copyType="iso8601"
      className="calendar-iso8601"
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
            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      }
    >
      <p className="text-lg font-mono text-indigo-600 dark:text-indigo-400 tracking-wider break-all">
        {data.value ?? 'N/A'}
      </p>
      <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-2 uppercase tracking-widest">
        international standard
      </p>
    </BaseCalendarCard>
  )
}
