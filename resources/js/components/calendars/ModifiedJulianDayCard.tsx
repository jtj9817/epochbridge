import { BaseCalendarCard, CalendarIcon } from './BaseCalendarCard'
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
      className="sd-card--modified-julian-day"
      icon={<CalendarIcon calendarKey="modified_julian_day" />}
    >
      <p className="sd-data-md text-[color:var(--sd-ink)] break-all">
        {data.day_number ?? data.value ?? 'N/A'}
      </p>
      <p className="sd-label mt-2 text-[color:var(--sd-ink-soft)]">
        MJD = JD - 2,400,000.5
      </p>
    </BaseCalendarCard>
  )
}
