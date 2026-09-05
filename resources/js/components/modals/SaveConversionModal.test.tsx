import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { SaveConversionModal } from './SaveConversionModal'

describe('SaveConversionModal', () => {
  const setup = ({
    open = true,
    isGuest = false,
    guestLimitReached = false,
  }: {
    open?: boolean
    isGuest?: boolean
    guestLimitReached?: boolean
  } = {}) => {
    const onClose = vi.fn()
    const onSave = vi.fn()

    const utils = render(
      <SaveConversionModal
        open={open}
        onClose={onClose}
        onSave={onSave}
        isGuest={isGuest}
        guestLimitReached={guestLimitReached}
      />
    )

    return { onClose, onSave, user: userEvent.setup(), rerender: utils.rerender, container: utils.container }
  }

  it('renders nothing when closed', () => {
    setup({ open: false })

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
  })

  it('renders the dialog with a note textarea when open', () => {
    setup()

    expect(screen.getByRole('dialog')).toBeInTheDocument()
    expect(
      screen.getByRole('heading', { name: 'Save Conversion' })
    ).toBeInTheDocument()
    expect(
      screen.getByPlaceholderText(/birthday, anniversary/i)
    ).toBeInTheDocument()
  })

  it('focuses the note textarea shortly after opening', async () => {
    setup()

    await waitFor(
      () => {
        expect(screen.getByPlaceholderText(/birthday, anniversary/i)).toHaveFocus()
      },
      { timeout: 1000 }
    )
  })

  it('shows the guest limit hint for guest users', () => {
    setup({ isGuest: true })

    expect(screen.getByText(/guest mode: 3 saves maximum/i)).toBeInTheDocument()
  })

  it('hides the guest hint for authenticated users', () => {
    setup({ isGuest: false })

    expect(screen.queryByText(/guest mode/i)).not.toBeInTheDocument()
  })

  it('updates the note and character counter while typing', async () => {
    const { user } = setup()

    const textarea = screen.getByPlaceholderText(/birthday, anniversary/i)
    await user.type(textarea, 'Hello')

    expect(textarea).toHaveValue('Hello')
    expect(screen.getByText('5/2048 characters')).toBeInTheDocument()
  })

  it('saves the trimmed note and closes via the parent handler', async () => {
    const { user, onSave, onClose } = setup()

    const textarea = screen.getByPlaceholderText(/birthday, anniversary/i)
    await user.type(textarea, '  Anniversary  ')
    await user.click(screen.getByRole('button', { name: 'Save' }))

    expect(onSave).toHaveBeenCalledTimes(1)
    expect(onSave).toHaveBeenCalledWith('Anniversary')
    expect(onClose).not.toHaveBeenCalled()
  })

  it('saves an empty note when nothing was typed', async () => {
    const { user, onSave } = setup()

    await user.click(screen.getByRole('button', { name: 'Save' }))

    expect(onSave).toHaveBeenCalledWith('')
  })

  it('clears the note after saving', async () => {
    const { user } = setup()

    const textarea = screen.getByPlaceholderText(/birthday, anniversary/i)
    await user.type(textarea, 'Birthday')
    await user.click(screen.getByRole('button', { name: 'Save' }))

    expect(textarea).toHaveValue('')
  })

  it('resets the note when the modal is reopened', async () => {
    const { user, rerender } = setup()

    const textarea = screen.getByPlaceholderText(/birthday, anniversary/i)
    await user.type(textarea, 'Stale note')

    rerender(
      <SaveConversionModal
        open={false}
        onClose={vi.fn()}
        onSave={vi.fn()}
        isGuest={false}
        guestLimitReached={false}
      />
    )
    rerender(
      <SaveConversionModal
        open={true}
        onClose={vi.fn()}
        onSave={vi.fn()}
        isGuest={false}
        guestLimitReached={false}
      />
    )

    expect(screen.getByPlaceholderText(/birthday, anniversary/i)).toHaveValue('')
  })

  it('closes via the Cancel button, close icon and overlay click', async () => {
    const { user, onClose, container } = setup()

    await user.click(screen.getByRole('button', { name: 'Cancel' }))
    expect(onClose).toHaveBeenCalledTimes(1)

    await user.click(screen.getByRole('button', { name: 'Close modal' }))
    expect(onClose).toHaveBeenCalledTimes(2)

    fireEvent.click(container.querySelector('.sd-overlay') as Element)
    expect(onClose).toHaveBeenCalledTimes(3)
  })

  it('closes when Escape is pressed inside the textarea', async () => {
    const { user, onClose } = setup()

    const textarea = screen.getByPlaceholderText(/birthday, anniversary/i)
    await user.click(textarea)
    await user.keyboard('{Escape}')

    expect(onClose).toHaveBeenCalledTimes(1)
  })

  it('shows the guest limit reached panel instead of the save form', () => {
    setup({ guestLimitReached: true })

    expect(
      screen.getByRole('heading', { name: 'Guest Limit Reached' })
    ).toBeInTheDocument()
    expect(
      screen.getByText(/guest limit of 3 saved conversions/i)
    ).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Create an account' })).toHaveAttribute(
      'href',
      '/register'
    )
    expect(
      screen.queryByPlaceholderText(/birthday, anniversary/i)
    ).not.toBeInTheDocument()
  })

  it('closes the guest limit panel from the OK button', async () => {
    const { user, onClose } = setup({ guestLimitReached: true })

    await user.click(screen.getByRole('button', { name: 'OK' }))

    expect(onClose).toHaveBeenCalledTimes(1)
  })
})
