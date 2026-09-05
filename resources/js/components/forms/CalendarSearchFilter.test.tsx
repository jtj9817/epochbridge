import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { useState } from 'react'
import { CalendarSearchFilter } from './CalendarSearchFilter'
import { CALENDAR_FILTER_TYPES, CALENDAR_FILTER_LABELS } from '@/types'

// Stateful wrapper so the controlled search input behaves like the real parent.
function StatefulHarness({
  initialSearchTerm = '',
  initialSelectedTypes = [],
  onSearchChange,
  onTypesChange,
}: {
  initialSearchTerm?: string
  initialSelectedTypes?: string[]
  onSearchChange: (value: string) => void
  onTypesChange: (types: string[]) => void
}) {
  const [searchTerm, setSearchTerm] = useState(initialSearchTerm)
  const [selectedTypes, setSelectedTypes] = useState(initialSelectedTypes)

  return (
    <CalendarSearchFilter
      searchTerm={searchTerm}
      selectedTypes={selectedTypes as never[]}
      onSearchChange={(value) => {
        setSearchTerm(value)
        onSearchChange(value)
      }}
      onTypesChange={(types) => {
        setSelectedTypes(types)
        onTypesChange(types)
      }}
    />
  )
}

describe('CalendarSearchFilter', () => {
  const setup = ({
    searchTerm = '',
    selectedTypes = [],
  }: {
    searchTerm?: string
    selectedTypes?: string[]
  } = {}) => {
    const onSearchChange = vi.fn()
    const onTypesChange = vi.fn()

    const utils = render(
      <CalendarSearchFilter
        searchTerm={searchTerm}
        selectedTypes={selectedTypes as never[]}
        onSearchChange={onSearchChange}
        onTypesChange={onTypesChange}
      />
    )

    return { onSearchChange, onTypesChange, user: userEvent.setup(), ...utils }
  }

  it('renders the search input with the current search term', () => {
    setup({ searchTerm: 'Jalali' })

    const input = screen.getByPlaceholderText(/search calendars/i)
    expect(input).toHaveValue('Jalali')
  })

  it('propagates search text changes to the parent callback', async () => {
    const onSearchChange = vi.fn()
    const user = userEvent.setup()
    render(<StatefulHarness onSearchChange={onSearchChange} onTypesChange={vi.fn()} />)

    const input = screen.getByPlaceholderText(/search calendars/i)
    await user.type(input, 'He')

    expect(onSearchChange).toHaveBeenNthCalledWith(1, 'H')
    expect(onSearchChange).toHaveBeenNthCalledWith(2, 'He')
    expect(input).toHaveValue('He')
  })

  it('propagates clearing the search input as an empty string', async () => {
    const { user, onSearchChange } = setup({ searchTerm: 'Mayan' })

    const input = screen.getByPlaceholderText(/search calendars/i)
    await user.clear(input)

    expect(onSearchChange).toHaveBeenCalledWith('')
  })

  it('hides the filter checkboxes until Show Filters is clicked', async () => {
    const { user } = setup()

    expect(
      screen.queryByRole('button', { name: 'Hide Filters' })
    ).not.toBeInTheDocument()
    expect(screen.queryByRole('checkbox')).not.toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: 'Show Filters' }))

    expect(
      screen.getByRole('button', { name: 'Hide Filters' })
    ).toBeInTheDocument()
    for (const type of CALENDAR_FILTER_TYPES) {
      expect(
        screen.getByRole('checkbox', { name: CALENDAR_FILTER_LABELS[type] })
      ).toBeInTheDocument()
    }
  })

  it('hides the filters again when Hide Filters is clicked', async () => {
    const { user } = setup()

    await user.click(screen.getByRole('button', { name: 'Show Filters' }))
    await user.click(screen.getByRole('button', { name: 'Hide Filters' }))

    expect(screen.queryByRole('checkbox')).not.toBeInTheDocument()
    expect(
      screen.getByRole('button', { name: 'Show Filters' })
    ).toBeInTheDocument()
  })

  it('marks checkboxes checked for the selected types', async () => {
    const { user } = setup({ selectedTypes: ['modern', 'lunar'] })

    await user.click(screen.getByRole('button', { name: 'Show Filters' }))

    expect(screen.getByRole('checkbox', { name: 'Modern' })).toBeChecked()
    expect(screen.getByRole('checkbox', { name: 'Lunar' })).toBeChecked()
    expect(screen.getByRole('checkbox', { name: 'Ancient' })).not.toBeChecked()
  })

  it('adds a type to the selection when an unchecked filter is toggled', async () => {
    const { user, onTypesChange } = setup({ selectedTypes: ['modern'] })

    await user.click(screen.getByRole('button', { name: 'Show Filters' }))
    await user.click(screen.getByRole('checkbox', { name: 'Lunar' }))

    expect(onTypesChange).toHaveBeenCalledWith(['modern', 'lunar'])
  })

  it('removes a type from the selection when a checked filter is toggled', async () => {
    const { user, onTypesChange } = setup({
      selectedTypes: ['modern', 'lunar'],
    })

    await user.click(screen.getByRole('button', { name: 'Show Filters' }))
    await user.click(screen.getByRole('checkbox', { name: 'Modern' }))

    expect(onTypesChange).toHaveBeenCalledWith(['lunar'])
  })
})
