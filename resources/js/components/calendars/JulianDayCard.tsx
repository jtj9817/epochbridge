import { BaseCalendarCard, CalendarIcon } from './BaseCalendarCard'
import { type NumericCalendarResult } from '@/types'

interface JulianDayCardProps {
  data: NumericCalendarResult
}

export function JulianDayCard({ data }: JulianDayCardProps) {
  return (
    <BaseCalendarCard
      title="Julian Day"
      copyText={data.full_date}
      copyType="julian_day"
      className="sd-card--julian-day"
      icon={<CalendarIcon calendarKey="julian_day" />}
    >
      <p className="sd-data-md text-[color:var(--sd-ink)] break-all">
        {data.day_number ?? data.value ?? 'N/A'}
      </p>
      <p className="sd-label mt-2 text-[color:var(--sd-ink-soft)]">
        astronomical day number
      </p>
    </BaseCalendarCard>
  )
}
