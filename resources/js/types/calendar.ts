/**
 * Calendar conversion API types for the React frontend.
 */

export const INPUT_TYPES = [
  'gregorian',
  'jalali',
  'unix',
  'iso8601',
  'jd',
  'mjd',
  'hebrew',
  'islamic',
  'french_republican',
  'chinese',
  'japanese',
  'coptic',
  'egyptian',
  'ethiopian',
  'babylonian',
  'assyrian',
  'macedonian',
  'mayan',
] as const

export type InputType = (typeof INPUT_TYPES)[number]

export const INPUT_TYPE_LABELS: Record<InputType, string> = {
  gregorian: 'Gregorian (YYYY-MM-DD)',
  jalali: 'Jalali (YYYY/MM/DD)',
  unix: 'Unix Timestamp',
  iso8601: 'ISO 8601',
  jd: 'Julian Day',
  mjd: 'Modified Julian Day',
  hebrew: 'Hebrew (YYYY/MM/DD)',
  islamic: 'Islamic (YYYY/MM/DD)',
  french_republican: 'French Republican (YYYY/MM/DD)',
  chinese: 'Chinese (YYYY/MM/DD)',
  japanese: 'Japanese (Era Year/MM/DD)',
  coptic: 'Coptic (YYYY/MM/DD)',
  egyptian: 'Egyptian (YYYY/MM/DD)',
  ethiopian: 'Ethiopian (YYYY/MM/DD)',
  babylonian: 'Babylonian (YYYY/MM/DD)',
  assyrian: 'Assyrian (YYYY/MM/DD)',
  macedonian: 'Ancient Macedonian (YYYY/MM/DD)',
  mayan: 'Mayan (from Gregorian YYYY/MM/DD)',
}

export const CALENDAR_FILTER_TYPES = [
  'modern',
  'historical',
  'ancient',
  'east-asian',
  'lunar',
  'solar',
  'lunisolar',
  'system',
] as const

export type CalendarFilterType = (typeof CALENDAR_FILTER_TYPES)[number]

export const CALENDAR_FILTER_LABELS: Record<CalendarFilterType, string> = {
  modern: 'Modern',
  historical: 'Historical',
  ancient: 'Ancient',
  'east-asian': 'East Asian',
  lunar: 'Lunar',
  solar: 'Solar',
  lunisolar: 'Lunisolar',
  system: 'System',
}

/**
 * Base calendar result with common fields.
 */
export interface BaseCalendarResult {
  key: string
  name: string
  tags: string[]
  full_date: string
}

/**
 * Structured calendar result with date components.
 */
export interface StructuredCalendarResult extends BaseCalendarResult {
  year: number | string
  month: number | string
  day: number | string
  month_name?: string
  day_of_week?: string
  era?: string
  cycle?: number
  cycle_year?: number
  stem_branch?: string
  long_count?: string
  tzolkin?: string
  haab?: string
}

/**
 * Numeric calendar result (unix, julian day, etc.).
 */
export interface NumericCalendarResult extends BaseCalendarResult {
  value: number | string
  day_number?: number
}

/**
 * Error calendar result.
 */
export interface ErrorCalendarResult {
  key: string
  name: string
  error: string
  tags: string[]
}

export type CalendarResult =
  | StructuredCalendarResult
  | NumericCalendarResult
  | ErrorCalendarResult

/**
 * Map of all conversion results keyed by calendar type.
 */
export type ConversionResultMap = Record<string, CalendarResult>

/**
 * API response from POST /frontend/conversions.
 */
export interface ConversionResponse {
  input: {
    date: string
    type: InputType
  }
  results: ConversionResultMap
  meta: {
    on_this_day_date: string | null
  }
}

/**
 * Quick action date response from server.
 */
export interface QuickActionResponse {
  date: string
  input_type: InputType
}

/**
 * On This Day content types.
 */
export interface OnThisDayEventPage {
  title: string
  extract?: string
  thumbnail?: { source: string }
  content_urls?: {
    desktop?: { page: string }
    mobile?: { page: string }
  }
}

export interface OnThisDayEvent {
  text: string
  year?: number
  pages?: OnThisDayEventPage[]
}

export interface OnThisDayData {
  selected?: OnThisDayEvent[]
  events?: OnThisDayEvent[]
  births?: OnThisDayEvent[]
  deaths?: OnThisDayEvent[]
  holidays?: OnThisDayEvent[]
}

export interface OnThisDayResponse {
  data: OnThisDayData
  date_label: string
  cached: boolean
  error?: string
}

/**
 * Type guard to check if a result is an error result.
 */
export function isErrorResult(
  result: CalendarResult
): result is ErrorCalendarResult {
  return 'error' in result
}

/**
 * Type guard to check if a result is a numeric result.
 */
export function isNumericResult(
  result: CalendarResult
): result is NumericCalendarResult {
  return 'value' in result && !('year' in result)
}

/**
 * Type guard to check if a result is a structured result.
 */
export function isStructuredResult(
  result: CalendarResult
): result is StructuredCalendarResult {
  return 'year' in result || 'long_count' in result
}
