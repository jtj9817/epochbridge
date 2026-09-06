import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { render, screen, fireEvent, waitFor } from '@/test/utils'
import LandingPage from './LandingPage'

// Mock Inertia
vi.mock('@inertiajs/react', () => ({
  Head: ({ title }: { title: string }) => <title data-testid="page-title">{title}</title>,
  Link: ({ children, href, id }: { children: React.ReactNode; href: string; id?: string }) => (
    <a href={href} id={id}>{children}</a>
  ),
  usePage: () => ({
    props: {
      auth: { user: null },
      ziggy: { url: 'http://localhost', routes: {}, location: '/' },
    },
  }),
}))

// Mock hooks
vi.mock('@/hooks/useSavedConversions', () => ({
  useSavedConversions: () => ({
    items: [],
    isLoading: false,
    error: null,
    save: vi.fn(),
    remove: vi.fn(),
    updateNote: vi.fn(),
    refresh: vi.fn(),
  }),
}))

vi.mock('@/hooks/useOnThisDay', () => ({
  useOnThisDay: () => ({
    data: null,
    isLoading: false,
    error: null,
    dateLabel: '',
    isOpen: false,
    open: vi.fn(),
    close: vi.fn(),
  }),
}))

// Mock fetch
const mockFetch = vi.fn()
global.fetch = mockFetch

// Helper to get the date input element
const getDateInput = () => document.getElementById('date-input') as HTMLInputElement

// Add CSRF meta tag before all tests
beforeEach(() => {
  mockFetch.mockReset()
  // Only add meta tag if it doesn't exist
  if (!document.querySelector('meta[name="csrf-token"]')) {
    const meta = document.createElement('meta')
    meta.name = 'csrf-token'
    meta.content = 'test-token'
    document.head.appendChild(meta)
  }
})

afterEach(() => {
  // Remove meta tag after test
  const meta = document.querySelector('meta[name="csrf-token"]')
  if (meta) {
    meta.remove()
  }
})

describe('LandingPage', () => {
  describe('Initial Rendering', () => {
    it('renders the page title and heading', () => {
      const { container } = render(<LandingPage todayDate="2024-01-15" />)

      // Check that the heading exists with the brand name
      const heading = container.querySelector('#main-heading')
      expect(heading).toBeInTheDocument()
      expect(heading).toHaveClass('sd-wordmark')
      expect(heading).toHaveTextContent('EpochBridge')
      expect(screen.getByText(/Universal calendar conversion system/)).toBeInTheDocument()
    })

    it('initializes date input with today date prop', () => {
      render(<LandingPage todayDate="2024-01-15" />)

      const dateInput = getDateInput()
      expect(dateInput.value).toBe('2024-01-15')
    })

    it('shows login and register links for unauthenticated users', () => {
      render(<LandingPage todayDate="2024-01-15" />)

      expect(screen.getByText('Log in')).toBeInTheDocument()
      expect(screen.getByText('Register')).toBeInTheDocument()
      expect(screen.queryByText('Dashboard')).not.toBeInTheDocument()
    })

    it('renders Calendars info button', () => {
      render(<LandingPage todayDate="2024-01-15" />)

      expect(screen.getByText('Calendars')).toBeInTheDocument()
    })

    it('does not show On This Day button before conversion', () => {
      render(<LandingPage todayDate="2024-01-15" />)

      expect(screen.queryByText('On This Day')).not.toBeInTheDocument()
    })
  })

  describe('Date Input and Type Selection', () => {
    it('allows changing the date input', async () => {
      render(<LandingPage todayDate="2024-01-15" />)

      const dateInput = getDateInput()
      fireEvent.change(dateInput, { target: { value: '2024-06-20' } })

      expect(dateInput.value).toBe('2024-06-20')
    })

    it('renders input type selector with Gregorian as default', () => {
      render(<LandingPage todayDate="2024-01-15" />)

      const select = screen.getByRole('combobox')
      expect(select).toHaveValue('gregorian')
    })

    it('allows changing input type', () => {
      render(<LandingPage todayDate="2024-01-15" />)

      const select = screen.getByRole('combobox')
      fireEvent.change(select, { target: { value: 'jalali' } })

      expect(select).toHaveValue('jalali')
    })
  })

  describe('Quick Actions', () => {
    it('renders TODAY, YESTERDAY, TOMORROW buttons', () => {
      render(<LandingPage todayDate="2024-01-15" />)

      expect(screen.getByText('TODAY')).toBeInTheDocument()
      expect(screen.getByText('YESTERDAY')).toBeInTheDocument()
      expect(screen.getByText('TOMORROW')).toBeInTheDocument()
    })
  })

  describe('Conversion Flow', () => {
    it('renders the Convert Date button', () => {
      render(<LandingPage todayDate="2024-01-15" />)

      expect(screen.getByText('CONVERT DATE')).toBeInTheDocument()
    })

    it('shows error message when date input is empty', async () => {
      render(<LandingPage todayDate="" />)

      const convertBtn = screen.getByText('CONVERT DATE')
      fireEvent.click(convertBtn)

      // Error should be displayed in the CalendarResultsGrid error element
      // Need to wait for React state update
      await waitFor(
        () => {
          expect(screen.getByText('Please enter a date value.')).toBeInTheDocument()
        },
        { timeout: 2000 }
      )
    })

    it('shows processing state during conversion', async () => {
      mockFetch.mockImplementation(() => new Promise(() => {})) // Never resolves

      render(<LandingPage todayDate="2024-01-15" />)

      const convertBtn = screen.getByText('CONVERT DATE')
      fireEvent.click(convertBtn)

      await waitFor(() => {
        expect(screen.getByText('PROCESSING...')).toBeInTheDocument()
      })
    })

    it('displays conversion results on successful conversion', async () => {
      const mockResults = {
        results: {
          gregorian: { name: 'Gregorian', full_date: '2024-01-15', year: 2024, month: 1, day: 15 },
          unix: { name: 'Unix Timestamp', full_date: '1705276800', value: 1705276800 },
        },
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResults),
      })

      render(<LandingPage todayDate="2024-01-15" />)

      const convertBtn = screen.getByText('CONVERT DATE')
      fireEvent.click(convertBtn)

      await waitFor(() => {
        expect(screen.getByText('Gregorian')).toBeInTheDocument()
      })
    })

    it('displays error message on failed conversion', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        json: () => Promise.resolve({ error: 'Invalid date format' }),
      })

      render(<LandingPage todayDate="invalid-date" />)

      const convertBtn = screen.getByText('CONVERT DATE')
      fireEvent.click(convertBtn)

      await waitFor(() => {
        expect(screen.getByText('Invalid date format')).toBeInTheDocument()
      })
    })

    it('handles network errors gracefully', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'))

      render(<LandingPage todayDate="2024-01-15" />)

      const convertBtn = screen.getByText('CONVERT DATE')
      fireEvent.click(convertBtn)

      await waitFor(() => {
        expect(screen.getByText('Failed to convert date. Please try again.')).toBeInTheDocument()
      })
    })

    it('sends correct request payload', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ results: {} }),
      })

      render(<LandingPage todayDate="2024-01-15" />)

      const select = screen.getByRole('combobox')
      fireEvent.change(select, { target: { value: 'unix' } })

      const dateInput = getDateInput()
      fireEvent.change(dateInput, { target: { value: '1705276800' } })

      const convertBtn = screen.getByText('CONVERT DATE')
      fireEvent.click(convertBtn)

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          '/frontend/conversions',
          expect.objectContaining({
            method: 'POST',
            body: JSON.stringify({ date: '1705276800', input_type: 'unix' }),
          })
        )
      })
    })

    it('opens the save modal via the results save button', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            results: {
              gregorian: { name: 'Gregorian', full_date: '2024-01-15' },
            },
          }),
      })

      render(<LandingPage todayDate="2024-01-15" />)

      fireEvent.click(screen.getByText('CONVERT DATE'))

      const resultsSaveBtn = await waitFor(() => {
        const button = document.querySelector(
          '[data-test="results-save-button"]'
        ) as HTMLElement
        expect(button).toBeTruthy()
        return button
      })

      fireEvent.click(resultsSaveBtn)

      await waitFor(() => {
        expect(screen.getByRole('dialog')).toBeInTheDocument()
      })
      expect(
        screen.getByRole('heading', { name: 'Save Conversion' })
      ).toBeInTheDocument()
    })
  })

  describe('Search and Filter (after results)', () => {
    it('shows search filter section after successful conversion', async () => {
      const mockResults = {
        results: {
          gregorian: { name: 'Gregorian', full_date: '2024-01-15' },
        },
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResults),
      })

      render(<LandingPage todayDate="2024-01-15" />)

      const convertBtn = screen.getByText('CONVERT DATE')
      fireEvent.click(convertBtn)

      await waitFor(() => {
        expect(screen.getByPlaceholderText(/search/i)).toBeInTheDocument()
      })
    })
  })

  describe('On This Day Button', () => {
    it('shows On This Day button after conversion with gregorian date', async () => {
      const mockResults = {
        results: {
          gregorian: { name: 'Gregorian', full_date: '2024-01-15', year: 2024, month: 1, day: 15 },
        },
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResults),
      })

      render(<LandingPage todayDate="2024-01-15" />)

      const convertBtn = screen.getByText('CONVERT DATE')
      fireEvent.click(convertBtn)

      await waitFor(() => {
        expect(screen.getByText('On This Day')).toBeInTheDocument()
      })
    })
  })

  describe('Calendars Info Modal', () => {
    it('opens Calendars Info modal when button is clicked', async () => {
      render(<LandingPage todayDate="2024-01-15" />)

      const calendarsBtn = screen.getByText('Calendars')
      fireEvent.click(calendarsBtn)

      // Modal should open - check for modal heading (h3)
      await waitFor(() => {
        const heading = screen.getByRole('heading', { name: 'Supported Calendar Systems' })
        expect(heading).toBeInTheDocument()
      })
    })
  })
})

// Note: Authenticated state rendering (Dashboard link) is tested at the
// integration level in tests/Feature/React/LandingPageTest.php since it
// requires proper Inertia page props from the server.
