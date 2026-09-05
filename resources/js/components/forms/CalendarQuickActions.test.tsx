import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { CalendarQuickActions } from './CalendarQuickActions'

describe('CalendarQuickActions', () => {
  const originalFetch = global.fetch
  let mockFetch: ReturnType<typeof vi.fn>
  let onDateSelect: ReturnType<typeof vi.fn>

  const setup = (inputType = 'gregorian') => {
    render(<CalendarQuickActions inputType={inputType as never} onDateSelect={onDateSelect} />)
    return userEvent.setup()
  }

  beforeEach(() => {
    mockFetch = vi.fn()
    onDateSelect = vi.fn()
    global.fetch = mockFetch as unknown as typeof fetch
    vi.spyOn(console, 'error').mockImplementation(() => {})
  })

  afterEach(() => {
    global.fetch = originalFetch
    vi.restoreAllMocks()
  })

  it('renders the today, yesterday and tomorrow quick actions', () => {
    setup()

    expect(screen.getByRole('button', { name: 'TODAY' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'YESTERDAY' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'TOMORROW' })).toBeInTheDocument()
  })

  it('requests today and passes the returned date to the callback', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ date: '2026-09-05', input_type: 'gregorian' }),
    })
    const user = setup()

    await user.click(screen.getByRole('button', { name: 'TODAY' }))

    expect(mockFetch).toHaveBeenCalledWith(
      '/frontend/quick-action?action=today&input_type=gregorian',
      { method: 'GET', headers: { Accept: 'application/json' } }
    )
    await waitFor(() => {
      expect(onDateSelect).toHaveBeenCalledTimes(1)
      expect(onDateSelect).toHaveBeenCalledWith('2026-09-05')
    })
  })

  it('requests yesterday with the current input type', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ date: '2026-09-04', input_type: 'unix' }),
    })
    const user = setup('unix')

    await user.click(screen.getByRole('button', { name: 'YESTERDAY' }))

    expect(mockFetch).toHaveBeenCalledWith(
      '/frontend/quick-action?action=yesterday&input_type=unix',
      { method: 'GET', headers: { Accept: 'application/json' } }
    )
    await waitFor(() => {
      expect(onDateSelect).toHaveBeenCalledWith('2026-09-04')
    })
  })

  it('requests tomorrow', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ date: '2026-09-06', input_type: 'gregorian' }),
    })
    const user = setup()

    await user.click(screen.getByRole('button', { name: 'TOMORROW' }))

    expect(mockFetch).toHaveBeenCalledWith(
      '/frontend/quick-action?action=tomorrow&input_type=gregorian',
      { method: 'GET', headers: { Accept: 'application/json' } }
    )
    await waitFor(() => {
      expect(onDateSelect).toHaveBeenCalledWith('2026-09-06')
    })
  })

  it('re-enables the buttons and restores labels once the action resolves', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ date: '2026-09-05', input_type: 'gregorian' }),
    })
    const user = setup()

    await user.click(screen.getByRole('button', { name: 'TODAY' }))

    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'TODAY' })).toBeEnabled()
    })
    expect(screen.getByRole('button', { name: 'YESTERDAY' })).toBeEnabled()
    expect(screen.getByRole('button', { name: 'TOMORROW' })).toBeEnabled()
  })

  it('disables all quick actions while a request is in flight', async () => {
    mockFetch.mockImplementation(() => new Promise(() => {}))
    const user = setup()

    await user.click(screen.getByRole('button', { name: 'TODAY' }))

    expect(screen.getByText('...')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'YESTERDAY' })).toBeDisabled()
    expect(screen.getByRole('button', { name: 'TOMORROW' })).toBeDisabled()
  })

  it('does not select a date when the server responds with an error', async () => {
    mockFetch.mockResolvedValueOnce({ ok: false, statusText: 'Server Error' })
    const user = setup()

    await user.click(screen.getByRole('button', { name: 'TODAY' }))

    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'TODAY' })).toBeEnabled()
    })
    expect(onDateSelect).not.toHaveBeenCalled()
  })

  it('does not select a date when the request fails', async () => {
    mockFetch.mockRejectedValueOnce(new Error('network down'))
    const user = setup()

    await user.click(screen.getByRole('button', { name: 'YESTERDAY' }))

    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'YESTERDAY' })).toBeEnabled()
    })
    expect(onDateSelect).not.toHaveBeenCalled()
    expect(console.error).toHaveBeenCalled()
  })
})
