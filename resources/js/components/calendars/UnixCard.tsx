import { BaseCalendarCard, CalendarIcon } from './BaseCalendarCard'
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
      className="sd-card--unix"
      icon={<CalendarIcon calendarKey="unix" />}
    >
      <p className="sd-data-md text-[color:var(--sd-ink)] break-all">
        {data.value ?? 'N/A'}
      </p>
      <p className="sd-label mt-2 text-[color:var(--sd-ink-soft)]">
        seconds since epoch
      </p>
    </BaseCalendarCard>
  )
}
