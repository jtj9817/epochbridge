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

  // Save Conversion modal, shared by the results grid and the saved
  // conversions section
  const [saveModalOpen, setSaveModalOpen] = useState(false)

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

      <div id="landing-page-container" className="sd-root min-h-screen">
        {/* Header */}
        <header
          id="landing-header"
          className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4"
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
                className="sd-btn sd-btn--ghost sd-btn--sm"
              >
                <img
                  src="/images/sd/icon-calendar.svg"
                  width={16}
                  height={16}
                  className="sd-pixel"
                  aria-hidden="true"
                />
                <span>Calendars</span>
              </button>
              {gregorianDateFromResults && (
                <button
                  id="btn-on-this-day"
                  onClick={() => onThisDay.open(gregorianDateFromResults)}
                  className="sd-btn sd-btn--ghost sd-btn--sm"
                >
                  <img
                    src="/images/sd/icon-clock.svg"
                    width={16}
                    height={16}
                    className="sd-pixel"
                    aria-hidden="true"
                  />
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
                  className="sd-btn sd-btn--ghost sd-btn--sm"
                >
                  Dashboard
                </Link>
              ) : (
                <>
                  <Link
                    id="link-login"
                    href="/login"
                    className="sd-btn sd-btn--ghost sd-btn--sm"
                  >
                    Log in
                  </Link>
                  <Link
                    id="link-register"
                    href="/register"
                    className="sd-btn sd-btn--primary sd-btn--sm"
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
            <div id="hero-section" className="mb-12 pt-6">
              {/* Decorative scenery */}
              <div
                aria-hidden="true"
                className="flex items-end justify-center gap-3 sm:gap-4 mb-1 select-none"
              >
                <img
                  src="/images/sd/sun.svg"
                  width={32}
                  height={32}
                  className="sd-pixel"
                />
                <img
                  src="/images/sd/grass-tuft.svg"
                  width={16}
                  height={16}
                  className="sd-pixel"
                />
                <img
                  src="/images/sd/flower-pink.svg"
                  width={16}
                  height={16}
                  className="sd-pixel"
                />
                <img
                  src="/images/sd/sprout.svg"
                  width={16}
                  height={16}
                  className="sd-pixel"
                />
                <img
                  src="/images/sd/flower-yellow.svg"
                  width={16}
                  height={16}
                  className="sd-pixel"
                />
                <img
                  src="/images/sd/sparkle.svg"
                  width={16}
                  height={16}
                  className="sd-pixel"
                />
              </div>
              <div aria-hidden="true" className="select-none opacity-90">
                <img
                  src="/images/sd/hills-strip.svg"
                  className="sd-pixel w-full h-10 sm:h-12 object-cover dark:hidden"
                />
                <img
                  src="/images/sd/hills-strip-night.svg"
                  className="sd-pixel w-full h-10 sm:h-12 object-cover hidden dark:block"
                />
              </div>

              {/* Title banner */}
              <div className="sd-panel sd-pop inline-block px-8 sm:px-12 py-6 mt-6">
                <h1 id="main-heading" className="sd-h1">
                  <span className="text-[color:var(--sd-ink)]">EPOCH</span>
                  <span className="text-[color:var(--sd-gold-text)]">
                    BRIDGE
                  </span>
                </h1>
              </div>
              <p
                id="subtitle"
                className="sd-small text-[color:var(--sd-cream)] mt-4 mb-3 max-w-2xl mx-auto"
              >
                Universal calendar conversion system.{' '}
                <span className="text-[color:var(--sd-gold-light)] font-semibold">
                  19 calendar systems.
                </span>{' '}
                Astronomical precision.
              </p>
              <div className="sd-label text-[color:var(--sd-gold)]">
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
                  className="sd-btn sd-btn--primary w-full disabled:cursor-not-allowed"
                >
                  {isConverting ? (
                    <span className="flex items-center">
                      <img
                        src="/images/sd/icon-clock.svg"
                        width={16}
                        height={16}
                        className="sd-pixel animate-spin mr-2"
                        aria-hidden="true"
                      />
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
              onSave={() => setSaveModalOpen(true)}
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
              saveModalOpen={saveModalOpen}
              onOpenSaveModal={() => setSaveModalOpen(true)}
              onCloseSaveModal={() => setSaveModalOpen(false)}
            />
          </div>
        </main>

        {/* Footer */}
        <footer
          id="landing-footer"
          className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-16 mb-8"
        >
          <div className="sd-panel sd-panel--thin px-4 py-4 text-center">
            <p className="sd-label text-[color:var(--sd-ink-soft)]">
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
