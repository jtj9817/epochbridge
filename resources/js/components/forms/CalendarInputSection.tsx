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
    <div id="date-input-section" className="mb-8">
      <div className="max-w-2xl mx-auto">
        {/* Input Type Selector */}
        <div className="mb-3">
          <label
            htmlFor="input-type-selector"
            className="block text-xs font-medium text-theme-500 dark:text-theme-100 mb-2 text-left uppercase tracking-wider"
          >
            Input Format
          </label>
          <select
            id="input-type-selector"
            value={inputType}
            onChange={(e) => onInputTypeChange(e.target.value as InputType)}
            className="w-full px-3 py-2 text-sm border-2 border-theme-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:border-theme-500 dark:focus:border-theme-100 transition-colors"
          >
            {INPUT_TYPES.map((type) => (
              <option key={type} value={type}>
                {INPUT_TYPE_LABELS[type]}
              </option>
            ))}
          </select>
        </div>

        {/* Main Input Field */}
        <div className="mb-6">
          <label
            htmlFor="date-input"
            className="block text-xs font-medium text-theme-500 dark:text-theme-100 mb-2 text-left uppercase tracking-wider"
          >
            Enter Value
          </label>
          <div className="relative datetime-input">
            <input
              type={inputHtmlType}
              id="date-input"
              value={dateInput}
              onChange={(e) => onDateChange(e.target.value)}
              step={inputType === 'jd' || inputType === 'mjd' ? 'any' : undefined}
              className="w-full px-4 py-3 text-base font-medium text-center border-0 bg-white dark:bg-zinc-900 text-theme-900 dark:text-theme-50 focus:outline-none transition-colors"
              placeholder={getPlaceholder()}
            />
          </div>
        </div>
      </div>
    </div>
  )
}
