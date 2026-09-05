import { BaseCalendarCard, CalendarIcon } from './BaseCalendarCard'
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
      className="sd-card--gregorian"
      icon={<CalendarIcon calendarKey="gregorian" />}
    >
      <div className="sd-label text-[color:var(--sd-ink-soft)]">
        {data.month_name}
      </div>
      <div className="sd-data-lg my-1 text-[color:var(--sd-ink)]">
        {data.day}
      </div>
      <div className="sd-small text-[color:var(--sd-ink-soft)]">
        {data.day_of_week}
      </div>
      <div className="sd-data-sm mt-2 text-[color:var(--sd-gold-text)]">
        {data.year}
      </div>
    </BaseCalendarCard>
  )
}
