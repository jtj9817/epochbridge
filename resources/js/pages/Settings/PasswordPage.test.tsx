import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { useState } from 'react'

const mockPatch = vi.fn()
const mockReset = vi.fn()

const formState = {
  errors: {} as Record<string, string>,
  processing: false,
  recentlySuccessful: false,
}

// Function declaration so the hoisted vi.mock factory can reference it safely.
function useMockForm(initial: Record<string, string>) {
  const [data, setData] = useState(initial)
  return {
    data,
    setData: (key: string, value: string) =>
      setData((prev) => ({ ...prev, [key]: value })),
    patch: mockPatch,
    processing: formState.processing,
    errors: formState.errors,
    recentlySuccessful: formState.recentlySuccessful,
    reset: mockReset,
    clearErrors: vi.fn(),
    setError: vi.fn(),
    hasErrors: Object.keys(formState.errors).length > 0,
  }
}

vi.mock('@inertiajs/react', () => ({
  Head: ({ title }: { title?: string }) =>
    title ? <title data-testid="page-title">{title}</title> : null,
  Link: ({
    children,
    href,
    ...rest
  }: {
    children: React.ReactNode
    href: string
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    [key: string]: any
  }) => (
    <a href={href} {...rest}>
      {children}
    </a>
  ),
  useForm: (initial: Record<string, string>) => useMockForm(initial),
  usePage: () => ({
    props: {
      auth: { user: { id: 1, name: 'Test User', email: 'test@example.com' } },
    },
    url: '/settings/password',
    component: 'Password',
    version: null,
  }),
}))

vi.stubGlobal('route', (name: string) => {
  const routes: Record<string, string> = {
    'user-password.update': '/user/password',
    dashboard: '/dashboard',
    'profile.edit': '/settings/profile',
    'password.edit': '/settings/password',
    'appearance.edit': '/settings/appearance',
    'two-factor.show': '/settings/two-factor',
    logout: '/logout',
  }
  return routes[name] ?? `/${name}`
})

import PasswordPage from './PasswordPage'


const dataTest = (name: string): HTMLElement =>
  document.querySelector(`[data-test="${name}"]`) as HTMLElement

describe('PasswordPage', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    formState.errors = {}
    formState.processing = false
    formState.recentlySuccessful = false
  })

  it('renders the current, new and confirm password fields', () => {
    render(<PasswordPage />)

    expect(screen.getByLabelText('Current password')).toBeInTheDocument()
    expect(screen.getByLabelText('New password')).toBeInTheDocument()
    expect(screen.getByLabelText('Confirm password')).toBeInTheDocument()
    expect(dataTest('update-password-button')).toBeInTheDocument()
  })

  it('starts with empty password fields and updates them while typing', async () => {
    const user = userEvent.setup()
    render(<PasswordPage />)

    const current = screen.getByLabelText('Current password')
    const next = screen.getByLabelText('New password')
    const confirmation = screen.getByLabelText('Confirm password')

    expect(current).toHaveValue('')
    expect(next).toHaveValue('')
    expect(confirmation).toHaveValue('')

    await user.type(current, 'old-secret')
    await user.type(next, 'new-secret-1')
    await user.type(confirmation, 'new-secret-1')

    expect(current).toHaveValue('old-secret')
    expect(next).toHaveValue('new-secret-1')
    expect(confirmation).toHaveValue('new-secret-1')
  })

  it('patches the password to the user-password.update route on submit', async () => {
    const user = userEvent.setup()
    render(<PasswordPage />)

    // Fill the required fields so native form validation does not block the submit.
    await user.type(screen.getByLabelText('Current password'), 'old-secret')
    await user.type(screen.getByLabelText('New password'), 'brand-new-secret-1')
    await user.type(screen.getByLabelText('Confirm password'), 'brand-new-secret-1')

    await user.click(dataTest('update-password-button'))

    expect(mockPatch).toHaveBeenCalledTimes(1)
    expect(mockPatch).toHaveBeenCalledWith('/user/password', {
      preserveScroll: true,
      onSuccess: expect.any(Function),
    })

    const { onSuccess } = mockPatch.mock.calls[0][1] as {
      onSuccess: () => void
    }
    onSuccess()
    expect(mockReset).toHaveBeenCalledWith(
      'current_password',
      'password',
      'password_confirmation'
    )
  })

  it('renders server validation errors for the password fields', () => {
    const { rerender } = render(<PasswordPage />)

    formState.errors = {
      current_password: 'The provided password does not match your current password.',
      password: 'The password must be at least 12 characters.',
    }
    rerender(<PasswordPage />)

    expect(
      screen.getByText(
        'The provided password does not match your current password.'
      )
    ).toBeInTheDocument()
    expect(
      screen.getByText('The password must be at least 12 characters.')
    ).toBeInTheDocument()
  })

  it('toggles password visibility for current and new password fields', async () => {
    const user = userEvent.setup()
    render(<PasswordPage />)

    const current = screen.getByLabelText('Current password')
    const next = screen.getByLabelText('New password')
    expect(current).toHaveAttribute('type', 'password')
    expect(next).toHaveAttribute('type', 'password')

    await user.click(toggleButtonFor('current_password'))
    expect(current).toHaveAttribute('type', 'text')
    expect(next).toHaveAttribute('type', 'password')

    await user.click(toggleButtonFor('password'))
    expect(next).toHaveAttribute('type', 'text')

    await user.click(toggleButtonFor('current_password'))
    expect(current).toHaveAttribute('type', 'password')
    expect(next).toHaveAttribute('type', 'text')
  })

  it('shows the saved confirmation after a successful patch', () => {
    const { rerender } = render(<PasswordPage />)

    formState.recentlySuccessful = true
    rerender(<PasswordPage />)

    expect(screen.getByText('Saved.')).toBeInTheDocument()
  })

  it('disables the save button while the form is processing', () => {
    const { rerender } = render(<PasswordPage />)

    formState.processing = true
    rerender(<PasswordPage />)

    expect(dataTest('update-password-button')).toBeDisabled()
  })
})

function toggleButtonFor(inputId: string): HTMLElement {
  const input = document.getElementById(inputId) as HTMLInputElement
  const wrapper = input.closest('div.relative') as HTMLElement
  return wrapper.querySelector('button') as HTMLElement
}
