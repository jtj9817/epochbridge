import { render, type RenderOptions } from '@testing-library/react'
import { type ReactElement, type ReactNode } from 'react'
import { ToastProvider } from '@/providers/ToastProvider'

// Mock Inertia page props
export interface MockPageProps {
  auth?: {
    user: { id: number; name: string; email: string } | null
  }
  ziggy?: {
    url: string
    port: number | null
    defaults: Record<string, unknown>
    routes: Record<string, { uri: string; methods: string[] }>
    location: string
  }
  flash?: {
    toast?: { type: 'success' | 'error' | 'info'; message: string }
  }
}

export const defaultMockPageProps: MockPageProps = {
  auth: { user: null },
  ziggy: {
    url: 'http://localhost',
    port: null,
    defaults: {},
    routes: {},
    location: 'http://localhost',
  },
}

export const authenticatedMockPageProps: MockPageProps = {
  ...defaultMockPageProps,
  auth: {
    user: { id: 1, name: 'Test User', email: 'test@example.com' },
  },
}

// Wrapper component for tests
function AllProviders({ children }: { children: ReactNode }) {
  return <ToastProvider>{children}</ToastProvider>
}

// Custom render function
function customRender(
  ui: ReactElement,
  options?: Omit<RenderOptions, 'wrapper'>
) {
  return render(ui, { wrapper: AllProviders, ...options })
}

// Re-export everything from Testing Library
export * from '@testing-library/react'
export { customRender as render }

// Mock conversion result for testing
export const mockConversionResult = {
  gregorian: {
    name: 'Gregorian',
    full_date: '2024-01-15',
    year: 2024,
    month: 1,
    day: 15,
    day_of_week: 'Monday',
  },
  jalali: {
    name: 'Jalali',
    full_date: '1402/10/25',
    year: 1402,
    month: 10,
    day: 25,
  },
  unix: {
    name: 'Unix Timestamp',
    full_date: '1705276800',
    value: 1705276800,
  },
  iso8601: {
    name: 'ISO 8601',
    full_date: '2024-01-15T00:00:00+00:00',
    value: '2024-01-15T00:00:00+00:00',
  },
  hebrew: {
    name: 'Hebrew',
    full_date: '5 Shevat 5784',
    year: 5784,
    month: 5,
    day: 5,
    month_name: 'Shevat',
  },
  islamic: {
    name: 'Islamic',
    full_date: '3 Rajab 1445',
    year: 1445,
    month: 7,
    day: 3,
    month_name: 'Rajab',
  },
}

// Mock saved conversion for testing
export const mockSavedConversion = {
  id: 1,
  original_input: '2024-01-15',
  note: 'Test note',
  conversion_data: mockConversionResult,
  uses_legacy_fallback: false,
  created_at: '2024-01-15T10:00:00Z',
}

// Mock guest saved conversion
export const mockGuestSavedConversion = {
  id: 'guest-1',
  original_input: '2024-01-15',
  conversion_data: mockConversionResult,
  note: '',
  created_at: '2024-01-15T10:00:00Z',
}
