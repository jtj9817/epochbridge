import { BaseCalendarCard, CalendarIcon } from './BaseCalendarCard'
import { type CalendarResult, isStructuredResult, isNumericResult } from '@/types'

interface GenericCalendarCardProps {
  calendarKey: string
  data: CalendarResult
}

export function GenericCalendarCard({ calendarKey, data }: GenericCalendarCardProps) {
  const disclaimer = 'disclaimer' in data ? (data as any).disclaimer : undefined

  // Handle Mayan calendar specially due to its unique structure
  if (calendarKey === 'mayan' && isStructuredResult(data)) {
    return (
      <BaseCalendarCard
        title={data.name}
        copyType={calendarKey}
        disclaimer={disclaimer}
        className={`sd-card--${calendarKey}`}
        icon={<CalendarIcon calendarKey={calendarKey} />}
      >
        <div className="space-y-3 w-full">
          <div>
            <p className="sd-label text-[color:var(--sd-ink-soft)]">
              Long Count
            </p>
            <p className="sd-data-md text-[color:var(--sd-ink)]">
              {data.long_count ?? 'N/A'}
            </p>
          </div>
          <div className="flex justify-between gap-4">
            <div className="flex-1">
              <p className="sd-label text-[color:var(--sd-ink-soft)]">
                Tzolk'in
              </p>
              <p className="sd-data-sm text-[color:var(--sd-ink)]">
                {data.tzolkin ?? 'N/A'}
              </p>
            </div>
            <div className="flex-1">
              <p className="sd-label text-[color:var(--sd-ink-soft)]">
                Haab'
              </p>
              <p className="sd-data-sm text-[color:var(--sd-ink)]">
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
        className={`sd-card--${calendarKey}`}
        icon={<CalendarIcon calendarKey={calendarKey} />}
      >
        <div className="sd-label text-[color:var(--sd-ink-soft)]">
          {data.month_name ?? `Month ${data.month}`}
        </div>
        <div className="sd-data-lg my-1 text-[color:var(--sd-ink)]">
          {data.day}
        </div>
        {data.stem_branch && (
          <div className="sd-small text-[color:var(--sd-ink-soft)]">
            {data.stem_branch}
          </div>
        )}
        <div className="sd-data-sm mt-2 text-[color:var(--sd-gold-text)]">
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
        className={`sd-card--${calendarKey}`}
        icon={<CalendarIcon calendarKey={calendarKey} />}
      >
        <div className="sd-label text-[color:var(--sd-ink-soft)]">
          {data.month_name ?? `Month ${data.month}`}
        </div>
        <div className="sd-data-lg my-1 text-[color:var(--sd-ink)]">
          {data.day}
        </div>
        {data.era && (
          <div className="sd-small text-[color:var(--sd-ink-soft)]">
            {data.era} {data.year}
          </div>
        )}
        {data.day_of_week && (
          <div className="sd-small mt-1 text-[color:var(--sd-ink-soft)]">
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
        className={`sd-card--${calendarKey}`}
        icon={<CalendarIcon calendarKey={calendarKey} />}
      >
        <div className="sd-label text-[color:var(--sd-ink-soft)]">
          {data.month_name ?? `Month ${data.month}`}
        </div>
        <div className="sd-data-lg my-1 text-[color:var(--sd-ink)]">
          {data.day}
        </div>
        {data.day_of_week && (
          <div className="sd-small text-[color:var(--sd-ink-soft)]">
            {data.day_of_week}
          </div>
        )}
        <div className="sd-data-sm mt-2 text-[color:var(--sd-gold-text)]">
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
        className={`sd-card--${calendarKey}`}
        icon={<CalendarIcon calendarKey={calendarKey} />}
      >
        <p className="sd-data-md text-[color:var(--sd-ink)] break-all">
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
      className={`sd-card--${calendarKey}`}
      icon={<CalendarIcon calendarKey={calendarKey} />}
    >
      <p className="sd-data-md text-[color:var(--sd-ink-soft)] break-all">
        {displayValue}
      </p>
    </BaseCalendarCard>
  )
}
