import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { OnThisDayModal } from './OnThisDayModal'
import type { OnThisDayData } from '@/types'

const sampleData: OnThisDayData = {
  selected: [
    {
      text: 'A pivotal treaty was signed.',
      year: 1445,
      pages: [
        {
          title: 'Treaty',
          content_urls: { desktop: { page: 'https://en.wikipedia.org/wiki/Treaty' } },
        },
      ],
    },
  ],
  events: [
    { text: 'The city was founded.', year: 1204 },
    { text: 'A famous battle occurred.', year: 1526 },
    { text: 'The bridge opened.', year: 1889 },
  ],
  births: [{ text: 'Notable person was born.', year: 1809 }],
}

describe('OnThisDayModal', () => {
  const setup = ({
    open = true,
    data = sampleData,
    isLoading = false,
    error = null,
    dateLabel = 'January 15',
  }: {
    open?: boolean
    data?: OnThisDayData | null
    isLoading?: boolean
    error?: string | null
    dateLabel?: string
  } = {}) => {
    const onClose = vi.fn()
    const utils = render(
      <OnThisDayModal
        open={open}
        onClose={onClose}
        data={data}
        isLoading={isLoading}
        error={error}
        dateLabel={dateLabel}
      />
    )
    return { onClose, user: userEvent.setup(), container: utils.container }
  }

  it('renders nothing when closed', () => {
    setup({ open: false })

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
  })

  it('renders the dialog with the date label and source note', () => {
    setup()

    expect(screen.getByRole('dialog')).toBeInTheDocument()
    expect(screen.getByText('On This Day — January 15')).toBeInTheDocument()
    expect(screen.getByText('English Wikipedia Highlights')).toBeInTheDocument()
  })

  it('renders the loading state', () => {
    setup({ isLoading: true, data: null })

    expect(screen.getByText('Loading historical highlights...')).toBeInTheDocument()
  })

  it('renders the error state', () => {
    setup({ error: 'Failed to load historical events. Please try again.' })

    expect(
      screen.getByText('Failed to load historical events. Please try again.')
    ).toBeInTheDocument()
    expect(screen.queryByText('Loading historical highlights...')).not.toBeInTheDocument()
  })

  it('renders the empty state when there is no content', () => {
    setup({ data: {} })

    expect(
      screen.getByText('No historical highlights available for this date yet.')
    ).toBeInTheDocument()
  })

  it('renders the empty state when data is null', () => {
    setup({ data: null })

    expect(
      screen.getByText('No historical highlights available for this date yet.')
    ).toBeInTheDocument()
  })

  it('renders each populated section with its entries and years', () => {
    setup()

    expect(screen.getByText('Selected')).toBeInTheDocument()
    expect(screen.getByText('Events')).toBeInTheDocument()
    expect(screen.getByText('Births')).toBeInTheDocument()
    expect(screen.queryByText('Deaths')).not.toBeInTheDocument()
    expect(screen.queryByText('Holidays')).not.toBeInTheDocument()

    expect(screen.getByText('A pivotal treaty was signed.')).toBeInTheDocument()
    expect(screen.getByText('1445')).toBeInTheDocument()
    expect(screen.getByText('The bridge opened.')).toBeInTheDocument()
    expect(screen.getByText('— 3 entries')).toBeInTheDocument()
  })

  it('links an entry to its Wikipedia page', () => {
    setup()

    const readLink = screen.getByRole('link', { name: /read/i })
    expect(readLink).toHaveAttribute('href', 'https://en.wikipedia.org/wiki/Treaty')
    expect(readLink).toHaveAttribute('target', '_blank')
    expect(readLink).toHaveAttribute('rel', 'noopener noreferrer')
  })

  it('does not render a link when the entry URL is unsafe', () => {
    setup({
      data: {
        selected: [
          {
            text: 'Suspicious entry.',
            year: 999,
            pages: [
              {
                title: 'Evil',
                content_urls: { desktop: { page: 'javascript:alert(1)' } },
              },
            ],
          },
        ],
      },
    })

    expect(screen.getByText('Suspicious entry.')).toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /read/i })).not.toBeInTheDocument()
  })

  it('collapses long sections behind a Show more toggle', async () => {
    const { user } = setup({
      data: {
        events: [
          { text: 'Event one', year: 1001 },
          { text: 'Event two', year: 1002 },
          { text: 'Event three', year: 1003 },
          { text: 'Event four', year: 1004 },
          { text: 'Event five', year: 1005 },
        ],
      },
    })

    expect(screen.getByText('Event one')).toBeInTheDocument()
    expect(screen.queryByText('Event four')).not.toBeInTheDocument()
    expect(screen.getByRole('button', { name: /show 2 more/i })).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: /show 2 more/i }))

    expect(screen.getByText('Event four')).toBeInTheDocument()
    expect(screen.getByText('Event five')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /show less/i })).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: /show less/i }))

    expect(screen.queryByText('Event four')).not.toBeInTheDocument()
  })

  it('closes via the Close button, close icon, overlay and Escape key', async () => {
    const { user, onClose, container } = setup()

    const closeButtons = screen.getAllByRole('button', { name: 'Close' })
    expect(closeButtons).toHaveLength(2)

    await user.click(closeButtons[0])
    expect(onClose).toHaveBeenCalledTimes(1)

    await user.click(closeButtons[1])
    expect(onClose).toHaveBeenCalledTimes(2)

    fireEvent.click(container.querySelector('.sd-overlay') as Element)
    expect(onClose).toHaveBeenCalledTimes(3)

    await user.keyboard('{Escape}')
    expect(onClose).toHaveBeenCalledTimes(4)
  })
})
