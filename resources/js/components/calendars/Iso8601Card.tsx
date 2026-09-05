import { BaseCalendarCard, CalendarIcon } from './BaseCalendarCard'
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
      className="sd-card--iso8601"
      icon={<CalendarIcon calendarKey="iso8601" />}
    >
      <p className="sd-data-md text-[color:var(--sd-ink)] break-all">
        {data.value ?? 'N/A'}
      </p>
      <p className="sd-label mt-2 text-[color:var(--sd-ink-soft)]">
        international standard
      </p>
    </BaseCalendarCard>
  )
}
