import { Head, Link, usePage } from '@inertiajs/react'
import { useState, useCallback, useMemo } from 'react'
import {
  type InputType,
  type ConversionResultMap,
  type CalendarFilterType,
} from '@/types'
import { CalendarInputSection } from '@/components/forms/CalendarInputSection'
import { CalendarQuickActions } from '@/components/forms/CalendarQuickActions'
import { CalendarSearchFilter } from '@/components/forms/CalendarSearchFilter'
import { CalendarResultsGrid } from '@/components/calendars/CalendarResultsGrid'
import { SavedConversionsSection } from '@/components/saved-conversions/SavedConversionsSection'
import { OnThisDayModal } from '@/components/modals/OnThisDayModal'
import { CalendarsInfoModal } from '@/components/modals/CalendarsInfoModal'
import { useSavedConversions } from '@/hooks/useSavedConversions'
import { useOnThisDay } from '@/hooks/useOnThisDay'

interface LandingPageProps {
  todayDate: string
}

export default function LandingPage({ todayDate }: LandingPageProps) {
  const { auth } = usePage().props

  // Form state
  const [dateInput, setDateInput] = useState(todayDate)
  const [inputType, setInputType] = useState<InputType>('gregorian')

  // Conversion state
  const [results, setResults] = useState<ConversionResultMap | null>(null)
  const [error, setError] = useState<string>('')
  const [isConverting, setIsConverting] = useState(false)

  // Filter state
  const [searchTerm, setSearchTerm] = useState('')
  const [selectedTypes, setSelectedTypes] = useState<CalendarFilterType[]>([])

  // Saved conversions (authenticated)
  const isAuthenticated = !!auth.user
  const savedConversions = useSavedConversions(isAuthenticated)

  // On This Day modal
  const onThisDay = useOnThisDay()

  // Calendars Info modal
  const [calendarsInfoOpen, setCalendarsInfoOpen] = useState(false)

  const currentConversionForSave = useMemo(
    () =>
      results && Object.keys(results).length > 0
        ? { originalInput: dateInput, conversionData: results }
        : null,
    [results, dateInput]
  )

  const gregorianDateFromResults = useMemo(() => {
    if (!results) return null
    const greg = results['gregorian']
    if (greg && 'full_date' in greg && typeof greg.full_date === 'string') {
      return greg.full_date
    }
    return null
  }, [results])
  const handleConvert = useCallback(async () => {
    if (!dateInput.trim()) {
      setError('Please enter a date value.')
      return
    }

    setIsConverting(true)
    setError('')

    try {
      const response = await fetch('/frontend/conversions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN':
            document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          date: dateInput,
          input_type: inputType,
        }),
      })

      const data = await response.json()

      if (!response.ok) {
        setError(data.error || data.message || 'Conversion failed.')
        setResults(null)
        return
      }

      setResults(data.results)
      setError('')
    } catch (err) {
      setError('Failed to convert date. Please try again.')
      setResults(null)
    } finally {
      setIsConverting(false)
    }
  }, [dateInput, inputType])

  const handleQuickAction = useCallback(
    (date: string) => {
      setDateInput(date)
    },
    []
  )

  const hasResults = results && Object.keys(results).length > 0

  return (
    <>
      <Head title="EpochBridge - Universal Calendar Conversion" />

      <div
        id="landing-page-container"
        className="min-h-screen bg-theme-50 dark:bg-theme-900"
      >
        {/* Header */}
        <header
          id="landing-header"
          className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 border-b border-theme-300 dark:border-zinc-700"
        >
          <nav
            id="auth-navigation"
            className="flex items-center justify-between gap-4"
          >
            {/* Left: Info buttons */}
            <div className="flex items-center gap-2">
              <button
                id="btn-calendars-info"
                onClick={() => setCalendarsInfoOpen(true)}
                className="inline-flex items-center gap-2 px-5 py-2 bg-theme-100 dark:bg-theme-700 border border-theme-300 dark:border-theme-600 rounded-lg text-sm font-medium text-theme-700 dark:text-theme-200 hover:bg-theme-200 dark:hover:bg-theme-600 transition-colors"
              >
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Calendars</span>
              </button>
              {gregorianDateFromResults && (
                <button
                  id="btn-on-this-day"
                  onClick={() => onThisDay.open(gregorianDateFromResults)}
                  className="inline-flex items-center gap-2 px-5 py-2 bg-theme-100 dark:bg-theme-700 border border-theme-300 dark:border-theme-600 rounded-lg text-sm font-medium text-theme-700 dark:text-theme-200 hover:bg-theme-200 dark:hover:bg-theme-600 transition-colors"
                >
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>On This Day</span>
                </button>
              )}
            </div>

            {/* Right: Auth Links */}
            <div className="flex items-center gap-2">
              {auth.user ? (
                <Link
                  id="link-dashboard"
                  href="/dashboard"
                  className="inline-block px-4 py-2 bg-theme-100 dark:bg-theme-500 border border-theme-300 dark:border-theme-300 text-xs font-medium text-theme-500 dark:text-theme-50 uppercase tracking-wider hover:bg-theme-50 dark:hover:bg-theme-300 transition-colors"
                >
                  Dashboard
                </Link>
              ) : (
                <>
                  <Link
                    id="link-login"
                    href="/login"
                    className="inline-block px-4 py-2 text-theme-500 dark:text-theme-50 hover:text-theme-900 dark:hover:text-white text-xs font-medium uppercase tracking-wider transition-colors border border-transparent hover:border-theme-300 dark:hover:border-zinc-600"
                  >
                    Log in
                  </Link>
                  <Link
                    id="link-register"
                    href="/register"
                    className="inline-block px-4 py-2 bg-theme-500 hover:bg-theme-300 text-white text-xs font-medium uppercase tracking-wider transition-colors border border-theme-500 hover:border-theme-300"
                  >
                    Register
                  </Link>
                </>
              )}
            </div>
          </nav>
        </header>

        {/* Main Content */}
        <main
          id="landing-main-content"
          className="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8"
        >
          <div className="w-full max-w-4xl mx-auto text-center">
            {/* Hero Section */}
            <div id="hero-section" className="mb-12 pt-8">
              <h1
                id="main-heading"
                className="text-5xl sm:text-6xl lg:text-7xl font-bold mb-4 tracking-tight"
              >
                <span className="text-theme-900 dark:text-theme-50">EPOCH</span>
                <span className="text-theme-300">BRIDGE</span>
              </h1>
              <p
                id="subtitle"
                className="text-base sm:text-lg text-theme-500 dark:text-theme-100 mb-6 max-w-2xl mx-auto leading-normal"
              >
                Universal calendar conversion system.{' '}
                <span className="text-theme-300 dark:text-theme-100 font-semibold">
                  19 calendar systems.
                </span>{' '}
                Astronomical precision.
              </p>
              <div className="text-xs text-theme-300 dark:text-theme-300 uppercase tracking-widest">
                Gregorian · Jalali · Hebrew · Islamic · Chinese · Julian Day ·
                Unix · ISO 8601
              </div>
            </div>

            <div className="max-w-2xl mx-auto">
              {/* Date Input Section */}
              <CalendarInputSection
                dateInput={dateInput}
                inputType={inputType}
                onDateChange={setDateInput}
                onInputTypeChange={setInputType}
              />

              {/* Quick Action Buttons */}
              <CalendarQuickActions
                inputType={inputType}
                onDateSelect={handleQuickAction}
              />

              {/* Convert Button */}
              <div className="flex justify-center">
                <button
                  id="btn-convert-date"
                  onClick={handleConvert}
                  disabled={isConverting}
                  className="btn-primary-technical w-full sm:w-auto relative flex items-center justify-center disabled:opacity-75 disabled:cursor-not-allowed"
                >
                  {isConverting ? (
                    <span className="flex items-center">
                      <svg
                        className="animate-spin -ml-1 mr-3 h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                      >
                        <circle
                          className="opacity-25"
                          cx="12"
                          cy="12"
                          r="10"
                          stroke="currentColor"
                          strokeWidth="4"
                        />
                        <path
                          className="opacity-75"
                          fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                      </svg>
                      PROCESSING...
                    </span>
                  ) : (
                    'CONVERT DATE'
                  )}
                </button>
              </div>
            </div>

            {/* Search and Filter Section */}
            {hasResults && (
              <CalendarSearchFilter
                searchTerm={searchTerm}
                selectedTypes={selectedTypes}
                onSearchChange={setSearchTerm}
                onTypesChange={setSelectedTypes}
              />
            )}

            {/* Conversion Results Section */}
            <CalendarResultsGrid
              results={results}
              error={error}
              searchTerm={searchTerm}
              selectedTypes={selectedTypes}
              onSave={() => {
                // Save is now handled by SavedConversionsSection's "Save Current" button
              }}
            />

            {/* Saved Conversions Section */}
            <SavedConversionsSection
              isAuthenticated={isAuthenticated}
              authItems={savedConversions.items}
              authLoading={savedConversions.isLoading}
              onAuthSave={savedConversions.save}
              onAuthDelete={savedConversions.remove}
              onAuthUpdateNote={savedConversions.updateNote}
              currentConversion={currentConversionForSave}
            />
          </div>
        </main>

        {/* Footer */}
        <footer
          id="landing-footer"
          className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 mt-16 border-t border-theme-300 dark:border-zinc-700"
        >
          <div className="text-center text-theme-500 dark:text-theme-100 text-xs uppercase tracking-widest">
            <p>
              &copy; {new Date().getFullYear()} EPOCHBRIDGE · REACT + INERTIA ·
              ASTRONOMICAL PRECISION
            </p>
          </div>
        </footer>
      </div>

      {/* Modals */}
      <OnThisDayModal
        open={onThisDay.isOpen}
        onClose={onThisDay.close}
        data={onThisDay.data}
        isLoading={onThisDay.isLoading}
        error={onThisDay.error}
        dateLabel={onThisDay.dateLabel}
      />
      <CalendarsInfoModal
        open={calendarsInfoOpen}
        onClose={() => setCalendarsInfoOpen(false)}
      />
    </>
  )
}
