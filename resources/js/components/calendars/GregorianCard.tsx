import { BaseCalendarCard } from './BaseCalendarCard'
import { type StructuredCalendarResult } from '@/types'

interface GregorianCardProps {
  data: StructuredCalendarResult
}

export function GregorianCard({ data }: GregorianCardProps) {
  return (
    <BaseCalendarCard
      title="Gregorian"
      copyText={data.full_date}
      copyType="gregorian"
      className="calendar-gregorian"
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
      <div className="text-xs font-medium text-blue-700 dark:text-blue-300 uppercase tracking-widest">
        {data.month_name}
      </div>
      <div className="text-5xl font-bold text-blue-600 dark:text-blue-400 my-1">
        {data.day}
      </div>
      <div className="text-sm text-blue-600 dark:text-blue-300 uppercase tracking-wider">
        {data.day_of_week}
      </div>
      <div className="text-xs font-mono text-blue-500 dark:text-blue-500 mt-2">
        {data.year}
      </div>
    </BaseCalendarCard>
  )
}
