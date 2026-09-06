import { describe, it, expect, vi, beforeEach } from 'vitest'
import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render } from '@/test/utils'
import { SavedConversionsSection } from './SavedConversionsSection'
import { mockConversionResult, mockSavedConversion } from '@/test/utils'
import { migrateGuestConversions } from '@/lib/api'
import type { SavedConversion } from '@/types'

vi.mock('@/lib/api', () => ({
  migrateGuestConversions: vi.fn().mockResolvedValue(undefined),
}))

const GUEST_STORAGE_KEY = 'savedConversions'

const authItem: SavedConversion = {
  id: 1,
  original_input: '2024-01-15',
  note: 'Test note',
  conversion_data: mockConversionResult as unknown as Record<string, unknown>,
  uses_legacy_fallback: false,
  created_at: '2024-01-15T10:00:00Z',
}

const secondAuthItem: SavedConversion = {
  ...authItem,
  id: 2,
  original_input: '1999-12-31',
  note: '',
  created_at: null,
}

const currentConversion = {
  originalInput: '2024-01-15',
  conversionData: mockConversionResult as unknown as Record<string, unknown>,
}

function seedGuestStorage(items: unknown[]) {
  localStorage.setItem(GUEST_STORAGE_KEY, JSON.stringify(items))
}

describe('SavedConversionsSection', () => {
  let onAuthSave: ReturnType<typeof vi.fn>
  let onAuthDelete: ReturnType<typeof vi.fn>
  let onAuthUpdateNote: ReturnType<typeof vi.fn>

  const setup = ({
    isAuthenticated = true,
    authItems = [authItem] as SavedConversion[],
    authLoading = false,
    withCurrentConversion = false,
  } = {}) => {
    const utils = render(
      <SavedConversionsSection
        isAuthenticated={isAuthenticated}
        authItems={authItems}
        authLoading={authLoading}
        onAuthSave={onAuthSave}
        onAuthDelete={onAuthDelete}
        onAuthUpdateNote={onAuthUpdateNote}
        currentConversion={withCurrentConversion ? currentConversion : null}
      />
    )
    return { user: userEvent.setup(), ...utils }
  }

  beforeEach(() => {
    localStorage.clear()
    vi.mocked(migrateGuestConversions).mockClear()
    onAuthSave = vi.fn().mockResolvedValue({ ...mockSavedConversion })
    onAuthDelete = vi.fn().mockResolvedValue(undefined)
    onAuthUpdateNote = vi.fn().mockResolvedValue(undefined)
  })

  it('lists each saved conversion with its original input', () => {
    setup({ authItems: [authItem, secondAuthItem] })

    expect(screen.getByTestId('saved-conversion-1')).toHaveTextContent('2024-01-15')
    expect(screen.getByTestId('saved-conversion-2')).toHaveTextContent('1999-12-31')
    expect(screen.getByText('Your Saved Conversions')).toBeInTheDocument()
  })

  it('shows the empty state when there are no saved conversions', () => {
    setup({ authItems: [] })

    expect(screen.getByText('No saved conversions yet.')).toBeInTheDocument()
  })

  it('shows a loading state while auth items are being fetched', () => {
    setup({ authItems: [], authLoading: true })

    expect(screen.getByText('Loading...')).toBeInTheDocument()
    expect(screen.queryByTestId('saved-conversion-1')).not.toBeInTheDocument()
  })

  it('expands an item to show its calendar conversion details', async () => {
    const { user } = setup()
    const item = screen.getByTestId('saved-conversion-1')

    expect(within(item).queryByText('Monday')).not.toBeInTheDocument()

    await user.click(
      within(item).getByRole('button', { name: 'Toggle conversion details' })
    )

    expect(within(item).getByText('Monday')).toBeInTheDocument()
    expect(within(item).getByText('Gregorian')).toBeInTheDocument()
  })

  it('hides calendar entries that contain errors when expanded', async () => {
    const { user } = setup({
      authItems: [
        {
          ...authItem,
          conversion_data: {
            gregorian: { name: 'Gregorian', full_date: '2024-01-15' },
            jalali: { name: 'Jalali', full_date: '1402/10/25' },
            mayan: { error: 'Mayan calendar conversion failed' },
          },
        },
      ],
    })
    const item = screen.getByTestId('saved-conversion-1')

    await user.click(
      within(item).getByRole('button', { name: 'Toggle conversion details' })
    )

    expect(within(item).getByText('Gregorian')).toBeInTheDocument()
    expect(within(item).getByText('Jalali')).toBeInTheDocument()
    expect(within(item).queryByText('Mayan')).not.toBeInTheDocument()
    expect(
      within(item).queryByText('Mayan calendar conversion failed')
    ).not.toBeInTheDocument()
  })

  it('shows the legacy indicator for legacy conversions', () => {
    setup({
      authItems: [{ ...authItem, uses_legacy_fallback: true }],
    })

    expect(screen.getByText('Legacy')).toBeInTheDocument()
  })

  describe('save flow', () => {
    it('opens the save modal, submits the note and calls onAuthSave with the full payload', async () => {
      const { user } = setup({ withCurrentConversion: true })

      await user.click(screen.getByRole('button', { name: 'Save Current' }))

      expect(screen.getByRole('dialog')).toBeInTheDocument()
      expect(
        screen.getByRole('heading', { name: 'Save Conversion' })
      ).toBeInTheDocument()

      await user.type(
        screen.getByPlaceholderText(/birthday, anniversary/i),
        'Anniversary'
      )
      await user.click(screen.getByRole('button', { name: 'Save' }))

      expect(onAuthSave).toHaveBeenCalledTimes(1)
      expect(onAuthSave).toHaveBeenCalledWith(
        '2024-01-15',
        currentConversion.conversionData,
        'Anniversary'
      )
      expect(await screen.findByText('Conversion saved successfully!')).toBeInTheDocument()
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    })

    it('saves without a note when the modal is submitted empty', async () => {
      const { user } = setup({ withCurrentConversion: true })

      await user.click(screen.getByRole('button', { name: 'Save Current' }))
      await user.click(screen.getByRole('button', { name: 'Save' }))

      expect(onAuthSave).toHaveBeenCalledWith(
        '2024-01-15',
        currentConversion.conversionData,
        ''
      )
    })

    it('shows an error toast when the save fails', async () => {
      onAuthSave.mockResolvedValue(null)
      const { user } = setup({ withCurrentConversion: true })

      await user.click(screen.getByRole('button', { name: 'Save Current' }))
      await user.click(screen.getByRole('button', { name: 'Save' }))

      expect(await screen.findByText('Failed to save conversion.')).toBeInTheDocument()
    })

    it('hides the Save Current button without a current conversion', () => {
      setup({ withCurrentConversion: false })

      expect(screen.queryByText('Save Current')).not.toBeInTheDocument()
    })
  })

  describe('edit note flow', () => {
    it('opens EditNoteModal pre-filled, saves the new note and toasts', async () => {
      const { user } = setup()
      const item = screen.getByTestId('saved-conversion-1')

      await user.click(
        within(item).getByRole('button', { name: 'Edit conversion note' })
      )

      const dialog = screen.getByRole('dialog')
      expect(within(dialog).getByRole('textbox')).toHaveValue('Test note')
      expect(
        within(dialog).getByRole('heading', { name: 'Edit Note' })
      ).toBeInTheDocument()

      await user.clear(within(dialog).getByRole('textbox'))
      await user.type(within(dialog).getByRole('textbox'), 'Updated note')
      await user.click(within(dialog).getByRole('button', { name: 'Save' }))

      expect(onAuthUpdateNote).toHaveBeenCalledTimes(1)
      expect(onAuthUpdateNote).toHaveBeenCalledWith(1, 'Updated note')
      expect(await screen.findByText('Note updated!')).toBeInTheDocument()
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    })

    it('discards changes when the note modal is cancelled', async () => {
      const { user } = setup()
      const item = screen.getByTestId('saved-conversion-1')

      await user.click(
        within(item).getByRole('button', { name: 'Edit conversion note' })
      )
      const dialog = screen.getByRole('dialog')
      await user.type(within(dialog).getByRole('textbox'), ' discarded')
      await user.click(within(dialog).getByRole('button', { name: 'Cancel' }))

      expect(onAuthUpdateNote).not.toHaveBeenCalled()
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    })

    it('offers the add-note action for items without a note', async () => {
      const { user } = setup({ authItems: [secondAuthItem] })
      const item = screen.getByTestId('saved-conversion-2')

      await user.click(
        within(item).getByRole('button', { name: 'Add conversion note' })
      )

      const dialog = screen.getByRole('dialog')
      expect(
        within(dialog).getByRole('heading', { name: 'Add Note' })
      ).toBeInTheDocument()
    })

    it('shows an error toast when the note update fails', async () => {
      onAuthUpdateNote.mockRejectedValueOnce(new Error('boom'))
      const { user } = setup()
      const item = screen.getByTestId('saved-conversion-1')

      await user.click(
        within(item).getByRole('button', { name: 'Edit conversion note' })
      )
      await user.click(
        within(screen.getByRole('dialog')).getByRole('button', { name: 'Save' })
      )

      expect(await screen.findByText('Failed to update note.')).toBeInTheDocument()
    })
  })

  describe('delete flow', () => {
    it('calls onAuthDelete with the item id and toasts on success', async () => {
      const { user } = setup()
      const item = screen.getByTestId('saved-conversion-1')

      await user.click(
        within(item).getByRole('button', { name: 'Delete saved conversion' })
      )

      expect(onAuthDelete).toHaveBeenCalledTimes(1)
      expect(onAuthDelete).toHaveBeenCalledWith(1)
      expect(
        await screen.findByText('Conversion deleted successfully!')
      ).toBeInTheDocument()
    })

    it('shows an error toast when the delete fails', async () => {
      onAuthDelete.mockRejectedValueOnce(new Error('boom'))
      const { user } = setup()

      await user.click(
        within(screen.getByTestId('saved-conversion-1')).getByRole('button', {
          name: 'Delete saved conversion',
        })
      )

      expect(await screen.findByText('Failed to delete conversion.')).toBeInTheDocument()
    })
  })

  describe('guest mode', () => {
    const guestItem = {
      id: 101,
      original_input: '2020-05-05',
      conversion_data: {
        gregorian: { name: 'Gregorian', full_date: '2020-05-05' },
      },
      note: 'guest note',
      created_at: '2020-05-05T00:00:00Z',
    }

    it('lists guest conversions from localStorage with the guest limit notice', () => {
      seedGuestStorage([guestItem])
      setup({ isAuthenticated: false, authItems: [] })

      expect(screen.getByTestId('saved-conversion-101')).toBeInTheDocument()
      expect(screen.getByText(/Guest mode: 1\/3 saves\./)).toBeInTheDocument()
    })

    it('deletes a guest conversion from localStorage', async () => {
      seedGuestStorage([guestItem])
      const { user } = setup({ isAuthenticated: false, authItems: [] })

      await user.click(
        within(screen.getByTestId('saved-conversion-101')).getByRole('button', {
          name: 'Delete saved conversion',
        })
      )

      expect(
        await screen.findByText('Conversion deleted successfully!')
      ).toBeInTheDocument()
      expect(screen.queryByTestId('saved-conversion-101')).not.toBeInTheDocument()
      expect(localStorage.getItem(GUEST_STORAGE_KEY)).toBe('[]')
    })

    it('updates a guest note in localStorage', async () => {
      seedGuestStorage([guestItem])
      const { user } = setup({ isAuthenticated: false, authItems: [] })
      const item = screen.getByTestId('saved-conversion-101')

      await user.click(
        within(item).getByRole('button', { name: 'Edit conversion note' })
      )
      const dialog = screen.getByRole('dialog')
      await user.clear(within(dialog).getByRole('textbox'))
      await user.type(within(dialog).getByRole('textbox'), 'New guest note')
      await user.click(within(dialog).getByRole('button', { name: 'Save' }))

      expect(await screen.findByText('Note updated!')).toBeInTheDocument()
      const stored = JSON.parse(
        localStorage.getItem(GUEST_STORAGE_KEY) ?? '[]'
      ) as Array<{ id: number; note: string }>
      expect(stored).toHaveLength(1)
      expect(stored[0].note).toBe('New guest note')
    })
  })

  describe('guest migration', () => {
    const guestItem = {
      id: 202,
      original_input: '2021-08-01',
      conversion_data: {
        gregorian: { name: 'Gregorian', full_date: '2021-08-01' },
      },
      note: '',
      created_at: '2021-08-01T00:00:00Z',
    }

    it('offers to migrate guest data after signing in and clears it on success', async () => {
      seedGuestStorage([guestItem])
      const { user, rerender } = setup({ isAuthenticated: false, authItems: [] })

      rerender(
        <SavedConversionsSection
          isAuthenticated={true}
          authItems={[]}
          authLoading={false}
          onAuthSave={onAuthSave}
          onAuthDelete={onAuthDelete}
          onAuthUpdateNote={onAuthUpdateNote}
          currentConversion={null}
        />
      )

      expect(screen.getByText(/1 guest conversion to migrate/)).toBeInTheDocument()

      await user.click(screen.getByRole('button', { name: 'Migrate now' }))

      expect(migrateGuestConversions).toHaveBeenCalledTimes(1)
      // Both the inline confirmation panel and the toast show this message.
      expect(
        await screen.findAllByText('Guest conversions migrated successfully!')
      ).toHaveLength(2)
      expect(localStorage.getItem(GUEST_STORAGE_KEY)).toBeNull()
    })

    it('shows an error toast when migration fails', async () => {
      vi.mocked(migrateGuestConversions).mockRejectedValueOnce(new Error('boom'))
      seedGuestStorage([guestItem])
      const { user, rerender } = setup({ isAuthenticated: false, authItems: [] })

      rerender(
        <SavedConversionsSection
          isAuthenticated={true}
          authItems={[]}
          authLoading={false}
          onAuthSave={onAuthSave}
          onAuthDelete={onAuthDelete}
          onAuthUpdateNote={onAuthUpdateNote}
          currentConversion={null}
        />
      )

      await user.click(screen.getByRole('button', { name: 'Migrate now' }))

      expect(
        await screen.findByText('Failed to migrate guest conversions.')
      ).toBeInTheDocument()
      expect(localStorage.getItem(GUEST_STORAGE_KEY)).not.toBeNull()
    })
  })
})
