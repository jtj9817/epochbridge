import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { EditNoteModal } from './EditNoteModal'

describe('EditNoteModal', () => {
  const setup = ({ open = true, initialValue = '' }: { open?: boolean; initialValue?: string } = {}) => {
    const onClose = vi.fn()
    const onSave = vi.fn()

    const utils = render(
      <EditNoteModal
        open={open}
        initialValue={initialValue}
        onSave={onSave}
        onClose={onClose}
      />
    )

    return { onClose, onSave, user: userEvent.setup(), container: utils.container }
  }

  it('renders nothing when closed', () => {
    setup({ open: false })

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
  })

  it('shows the Add Note title when there is no existing note', () => {
    setup({ initialValue: '' })

    expect(screen.getByRole('heading', { name: 'Add Note' })).toBeInTheDocument()
  })

  it('shows the Edit Note title and pre-fills the existing note', () => {
    setup({ initialValue: 'Anniversary dinner' })

    expect(screen.getByRole('heading', { name: 'Edit Note' })).toBeInTheDocument()
    expect(screen.getByRole('textbox')).toHaveValue('Anniversary dinner')
  })

  it('re-syncs the textarea when reopened with a different note', () => {
    const { rerender } = render(
      <EditNoteModal open={true} initialValue="First" onSave={vi.fn()} onClose={vi.fn()} />
    )

    rerender(
      <EditNoteModal open={false} initialValue="First" onSave={vi.fn()} onClose={vi.fn()} />
    )
    rerender(
      <EditNoteModal open={true} initialValue="Second" onSave={vi.fn()} onClose={vi.fn()} />
    )

    expect(screen.getByRole('textbox')).toHaveValue('Second')
  })

  it('saves the edited note trimmed', async () => {
    const { user, onSave } = setup({ initialValue: 'Old note' })

    const textarea = screen.getByRole('textbox')
    await user.clear(textarea)
    await user.type(textarea, '  Updated note  ')
    await user.click(screen.getByRole('button', { name: 'Save' }))

    expect(onSave).toHaveBeenCalledTimes(1)
    expect(onSave).toHaveBeenCalledWith('Updated note')
  })

  it('switches the title back to Add Note when the text is cleared', async () => {
    const { user } = setup({ initialValue: 'Temp' })

    await user.clear(screen.getByRole('textbox'))

    expect(screen.getByRole('heading', { name: 'Add Note' })).toBeInTheDocument()
  })

  it('does not save when cancelled', async () => {
    const { user, onSave, onClose } = setup({ initialValue: 'Keep me' })

    await user.type(screen.getByRole('textbox'), ' more')
    await user.click(screen.getByRole('button', { name: 'Cancel' }))

    expect(onSave).not.toHaveBeenCalled()
    expect(onClose).toHaveBeenCalledTimes(1)
  })

  it('discards the draft when reopened after cancelling', async () => {
    const onClose = vi.fn()
    const onSave = vi.fn()
    const user = userEvent.setup()
    const { rerender } = render(
      <EditNoteModal open={true} initialValue="Original" onSave={onSave} onClose={onClose} />
    )

    await user.clear(screen.getByRole('textbox'))
    await user.type(screen.getByRole('textbox'), 'Draft that should be discarded')
    await user.click(screen.getByRole('button', { name: 'Cancel' }))

    rerender(
      <EditNoteModal open={false} initialValue="Original" onSave={onSave} onClose={onClose} />
    )
    rerender(
      <EditNoteModal open={true} initialValue="Original" onSave={onSave} onClose={onClose} />
    )

    expect(screen.getByRole('textbox')).toHaveValue('Original')
    expect(onSave).not.toHaveBeenCalled()
  })

  it('saves with Ctrl+Enter', async () => {
    const { user, onSave } = setup({ initialValue: 'Shortcut note' })

    await user.click(screen.getByRole('textbox'))
    await user.keyboard('{Control>}{Enter}')

    expect(onSave).toHaveBeenCalledWith('Shortcut note')
  })

  it('closes with Escape without saving', async () => {
    const { user, onSave, onClose } = setup({ initialValue: 'Note' })

    await user.click(screen.getByRole('textbox'))
    await user.keyboard('{Escape}')

    expect(onClose).toHaveBeenCalledTimes(1)
    expect(onSave).not.toHaveBeenCalled()
  })

  it('closes via the close icon and the overlay', async () => {
    const { user, onClose, container } = setup({ initialValue: 'Note' })

    await user.click(screen.getByRole('button', { name: 'Close modal' }))
    expect(onClose).toHaveBeenCalledTimes(1)

    fireEvent.click(container.querySelector('.sd-overlay') as Element)
    expect(onClose).toHaveBeenCalledTimes(2)
  })

  it('shows the character counter while typing', async () => {
    const { user } = setup({ initialValue: '' })

    await user.type(screen.getByRole('textbox'), 'abc')

    expect(screen.getByText('3/2048')).toBeInTheDocument()
  })
})
