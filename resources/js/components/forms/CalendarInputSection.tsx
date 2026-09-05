import { type InputType, INPUT_TYPES, INPUT_TYPE_LABELS } from '@/types'

interface CalendarInputSectionProps {
  dateInput: string
  inputType: InputType
  onDateChange: (value: string) => void
  onInputTypeChange: (type: InputType) => void
}

export function CalendarInputSection({
  dateInput,
  inputType,
  onDateChange,
  onInputTypeChange,
}: CalendarInputSectionProps) {
  const getPlaceholder = (): string => {
    switch (inputType) {
      case 'gregorian':
        return 'YYYY-MM-DD'
      case 'jalali':
      case 'hebrew':
      case 'islamic':
      case 'french_republican':
      case 'chinese':
      case 'babylonian':
      case 'assyrian':
      case 'macedonian':
      case 'coptic':
      case 'egyptian':
      case 'ethiopian':
      case 'mayan':
        return 'YYYY/MM/DD'
      case 'unix':
        return 'e.g., 1672531200'
      case 'iso8601':
        return 'e.g., 2023-01-01T00:00:00Z'
      case 'jd':
        return 'e.g., 2460310.5'
      case 'mjd':
        return 'e.g., 60309'
      case 'japanese':
        return 'Reiwa 5/05/01'
      default:
        return 'Enter value'
    }
  }

  const getInputType = (): string => {
    switch (inputType) {
      case 'gregorian':
        return 'date'
      case 'unix':
      case 'jd':
      case 'mjd':
        return 'number'
      default:
        return 'text'
    }
  }

  const inputHtmlType = getInputType()

  return (
    <div id="date-input-section" className="sd-panel p-6 mb-8">
      {/* Input Type Selector */}
      <div className="mb-4">
        <label
          htmlFor="input-type-selector"
          className="sd-label block text-left mb-2 text-[color:var(--sd-ink)]"
        >
          Input Format
        </label>
        <div className="relative">
          <select
            id="input-type-selector"
            value={inputType}
            onChange={(e) => onInputTypeChange(e.target.value as InputType)}
            className="sd-select pr-8"
          >
            {INPUT_TYPES.map((type) => (
              <option key={type} value={type}>
                {INPUT_TYPE_LABELS[type]}
              </option>
            ))}
          </select>
          <span
            aria-hidden="true"
            className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 sd-small text-[color:var(--sd-ink)]"
          >
            ▼
          </span>
        </div>
      </div>

      {/* Main Input Field */}
      <div>
        <label
          htmlFor="date-input"
          className="sd-label block text-left mb-2 text-[color:var(--sd-ink)]"
        >
          Enter Value
        </label>
        <div className="relative">
          <input
            type={inputHtmlType}
            id="date-input"
            value={dateInput}
            onChange={(e) => onDateChange(e.target.value)}
            step={inputType === 'jd' || inputType === 'mjd' ? 'any' : undefined}
            className="sd-input text-base font-medium text-center"
            placeholder={getPlaceholder()}
          />
        </div>
      </div>
    </div>
  )
}
