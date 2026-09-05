import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { CalendarResultsGrid } from './CalendarResultsGrid'
import { mockConversionResult } from '@/test/utils'
import type { ConversionResultMap } from '@/types'

const taggedResults = {
  gregorian: {
    key: 'gregorian',
    name: 'Gregorian',
    tags: ['modern', 'solar'],
    full_date: '2024-01-15',
    year: 2024,
    month: 1,
    day: 15,
    month_name: 'January',
    day_of_week: 'Monday',
  },
  hebrew: {
    key: 'hebrew',
    name: 'Hebrew',
    tags: ['lunar', 'historical'],
    full_date: '5 Shevat 5784',
    year: 5784,
    month: 5,
    day: 5,
    month_name: 'Shevat',
  },
  unix: {
    key: 'unix',
    name: 'Unix Timestamp',
    tags: ['system'],
    full_date: '1705276800',
    value: 1705276800,
  },
} as unknown as ConversionResultMap

describe('CalendarResultsGrid', () => {
  beforeEach(() => {
    Object.defineProperty(navigator, 'clipboard', {
      value: { writeText: vi.fn().mockResolvedValue(undefined) },
      configurable: true,
    })
  })

  const setup = ({
    results = mockConversionResult as unknown as ConversionResultMap,
    error = '',
    searchTerm = '',
    selectedTypes = [],
    onSave,
  }: {
    results?: ConversionResultMap | null
    error?: string
    searchTerm?: string
    selectedTypes?: string[]
    onSave?: () => void
  } = {}) => {
    const utils = render(
      <CalendarResultsGrid
        results={results}
        error={error}
        searchTerm={searchTerm}
        selectedTypes={selectedTypes as never[]}
        onSave={onSave}
      />
    )
    return { user: userEvent.setup(), ...utils }
  }

  it('renders nothing when there are no results yet', () => {
    const { container } = setup({ results: null })

    expect(container).toBeEmptyDOMElement()
  })

  it('renders nothing when the results map is empty', () => {
    const { container } = setup({ results: {} })

    expect(container).toBeEmptyDOMElement()
  })

  it('renders the error state instead of cards when an error is present', () => {
    setup({ error: 'Invalid date format' })

    expect(screen.getByText('Invalid date format')).toBeInTheDocument()
    expect(screen.queryByText('Conversion Results')).not.toBeInTheDocument()
  })

  it('renders a card for every calendar in the conversion payload', () => {
    setup()

    expect(
      screen.getByRole('heading', { name: 'Conversion Results' })
    ).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Gregorian' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Jalali' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Unix' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'ISO 8601' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Hebrew' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Islamic' })).toBeInTheDocument()
  })

  it('skips calendars that returned an error result', () => {
    setup({
      results: {
        gregorian: mockConversionResult.gregorian,
        broken: {
          key: 'broken',
          name: 'Broken Calendar',
          error: 'Conversion failed',
          tags: [],
        },
      } as unknown as ConversionResultMap,
    })

    expect(screen.getByRole('heading', { name: 'Gregorian' })).toBeInTheDocument()
    expect(
      screen.queryByRole('heading', { name: 'Broken Calendar' })
    ).not.toBeInTheDocument()
  })

  it('calls the save handler when SAVE is clicked', async () => {
    const onSave = vi.fn()
    const { user } = setup({ onSave })

    await user.click(screen.getByRole('button', { name: 'SAVE' }))

    expect(onSave).toHaveBeenCalledTimes(1)
  })

  it('renders SAVE without crashing when no save handler is provided', () => {
    setup({ onSave: undefined })

    expect(screen.getByRole('button', { name: 'SAVE' })).toBeInTheDocument()
  })

  it('copies all visible conversions to the clipboard and shows feedback', async () => {
    // userEvent.setup() installs its own clipboard stub, so spy on it after render.
    const { user } = setup({
      results: {
        gregorian: mockConversionResult.gregorian,
        unix: mockConversionResult.unix,
      } as unknown as ConversionResultMap,
    })
    const writeText = vi
      .spyOn(navigator.clipboard, 'writeText')
      .mockResolvedValue(undefined)

    await user.click(screen.getByRole('button', { name: 'COPY ALL' }))

    await waitFor(() => {
      expect(writeText).toHaveBeenCalledWith(
        'Gregorian: 2024-01-15\nUnix Timestamp: 1705276800'
      )
    })
    expect(screen.getByText('COPIED')).toBeInTheDocument()
  })

  it('filters cards by the search term', () => {
    setup({ results: taggedResults, searchTerm: 'greg' })

    expect(screen.getByRole('heading', { name: 'Gregorian' })).toBeInTheDocument()
    expect(screen.queryByRole('heading', { name: 'Hebrew' })).not.toBeInTheDocument()
    expect(screen.queryByRole('heading', { name: 'Unix' })).not.toBeInTheDocument()
  })

  it('shows the no-match message when no calendar matches the search term', () => {
    setup({ results: taggedResults, searchTerm: 'zzz-does-not-exist' })

    expect(
      screen.getByText('No calendars match your search criteria.')
    ).toBeInTheDocument()
    expect(screen.queryByRole('heading', { name: 'Gregorian' })).not.toBeInTheDocument()
  })

  it('filters cards by the selected calendar types', () => {
    setup({ results: taggedResults, selectedTypes: ['lunar'] })

    expect(screen.getByRole('heading', { name: 'Hebrew' })).toBeInTheDocument()
    expect(screen.queryByRole('heading', { name: 'Gregorian' })).not.toBeInTheDocument()
    expect(screen.queryByRole('heading', { name: 'Unix' })).not.toBeInTheDocument()
  })

  it('shows all cards when no type filter is selected', () => {
    setup({ results: taggedResults, selectedTypes: [] })

    expect(screen.getByRole('heading', { name: 'Gregorian' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Hebrew' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Unix' })).toBeInTheDocument()
  })
})
