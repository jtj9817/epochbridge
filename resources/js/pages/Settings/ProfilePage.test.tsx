import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { useState } from 'react'

const mockPut = vi.fn()

const formState = {
  data: {} as Record<string, string>,
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
    patch: vi.fn(),
    put: mockPut,
    processing: formState.processing,
    errors: formState.errors,
    recentlySuccessful: formState.recentlySuccessful,
    reset: vi.fn(),
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
    url: '/settings/profile',
    component: 'Profile',
    version: null,
  }),
}))

vi.stubGlobal('route', (name: string) => {
  const routes: Record<string, string> = {
    'user-profile-information.update': '/user/profile-information',
    'current-user.destroy': '/user',
    'verification.send': '/email/verification-notification',
    dashboard: '/dashboard',
    'profile.edit': '/settings/profile',
    'password.edit': '/settings/password',
    'appearance.edit': '/settings/appearance',
    'two-factor.show': '/settings/two-factor',
    logout: '/logout',
  }
  return routes[name] ?? `/${name}`
})

import ProfilePage from './ProfilePage'

const userProps = {
  name: 'Test User',
  email: 'test@example.com',
  has_verified_email: true,
}


const dataTest = (name: string): HTMLElement =>
  document.querySelector(`[data-test="${name}"]`) as HTMLElement

describe('ProfilePage', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    formState.errors = {}
    formState.processing = false
    formState.recentlySuccessful = false
    if (!document.querySelector('meta[name="csrf-token"]')) {
      const meta = document.createElement('meta')
      meta.name = 'csrf-token'
      meta.content = 'test-token'
      document.head.appendChild(meta)
    }
  })

  afterEach(() => {
    document
      .querySelectorAll('form[action="/user"]')
      .forEach((form) => form.remove())
  })

  it('renders the profile form with the current name and email', () => {
    render(<ProfilePage user={userProps} status={null} />)

    expect(screen.getByLabelText('Name')).toHaveValue('Test User')
    expect(screen.getByLabelText('Email')).toHaveValue('test@example.com')
    expect(
      screen.getByRole('button', { name: 'Save' })
    ).toBeInTheDocument()
  })

  it('updates the fields as the user types', async () => {
    const user = userEvent.setup()
    render(<ProfilePage user={userProps} status={null} />)

    await user.clear(screen.getByLabelText('Name'))
    await user.type(screen.getByLabelText('Name'), 'New Name')

    expect(screen.getByLabelText('Name')).toHaveValue('New Name')
  })

  it('puts the profile to the user-profile-information.update route', async () => {
    const user = userEvent.setup()
    render(<ProfilePage user={userProps} status={null} />)

    await user.click(dataTest('update-profile-button'))

    expect(mockPut).toHaveBeenCalledTimes(1)
    expect(mockPut).toHaveBeenCalledWith('/user/profile-information', {
      preserveScroll: true,
    })
  })

  it('renders server validation errors for name and email', () => {
    const { rerender } = render(<ProfilePage user={userProps} status={null} />)

    formState.errors = {
      name: 'The name field is required.',
      email: 'The email has already been taken.',
    }
    rerender(<ProfilePage user={userProps} status={null} />)

    expect(screen.getByText('The name field is required.')).toBeInTheDocument()
    expect(
      screen.getByText('The email has already been taken.')
    ).toBeInTheDocument()
  })

  it('shows the saved confirmation after a successful update', () => {
    const { rerender } = render(<ProfilePage user={userProps} status={null} />)

    expect(screen.queryByText('Saved.')).not.toBeInTheDocument()

    formState.recentlySuccessful = true
    rerender(<ProfilePage user={userProps} status={null} />)

    expect(screen.getByText('Saved.')).toBeInTheDocument()
  })

  it('disables the save button while the form is processing', () => {
    const { rerender } = render(<ProfilePage user={userProps} status={null} />)

    formState.processing = true
    rerender(<ProfilePage user={userProps} status={null} />)

    expect(dataTest('update-profile-button')).toBeDisabled()
  })

  it('offers to re-send the verification email for unverified addresses', () => {
    render(
      <ProfilePage
        user={{ ...userProps, has_verified_email: false }}
        status={null}
      />
    )

    expect(screen.getByText(/your email address is unverified/i)).toBeInTheDocument()
    expect(
      screen.getByRole('button', { name: /re-send the verification email/i })
    ).toBeInTheDocument()
  })

  it('shows the verification sent status message', () => {
    render(
      <ProfilePage
        user={{ ...userProps, has_verified_email: false }}
        status="verification-link-sent"
      />
    )

    expect(
      screen.getByText(
        'A new verification link has been sent to your email address.'
      )
    ).toBeInTheDocument()
  })

  it('opens the delete account confirmation modal', async () => {
    const user = userEvent.setup()
    render(<ProfilePage user={userProps} status={null} />)

    await user.click(dataTest('delete-user-button'))

    expect(
      screen.getByText(/are you sure you want to delete your account\?/i)
    ).toBeInTheDocument()
    expect(screen.getByLabelText('Password')).toBeInTheDocument()
  })

  it('submits account deletion as a DELETE form to the current-user.destroy route', async () => {
    const submitSpy = vi
      .spyOn(HTMLFormElement.prototype, 'submit')
      .mockImplementation(() => {})
    const user = userEvent.setup()
    render(<ProfilePage user={userProps} status={null} />)

    await user.click(dataTest('delete-user-button'))
    await user.type(screen.getByLabelText('Password'), 'secret-password')
    await user.click(dataTest('confirm-delete-user-button'))

    expect(submitSpy).toHaveBeenCalledTimes(1)
    const form = submitSpy.mock.instances[0] as HTMLFormElement
    expect(form.action).toMatch(/\/user$/)
    expect(form.querySelector('input[name="_method"]')).toHaveValue('DELETE')
    expect(form.querySelector('input[name="password"]')).toHaveValue(
      'secret-password'
    )
    expect(form.querySelector('input[name="_token"]')).toHaveValue('test-token')

    submitSpy.mockRestore()
  })

  it('closes the delete modal without submitting on cancel', async () => {
    const submitSpy = vi
      .spyOn(HTMLFormElement.prototype, 'submit')
      .mockImplementation(() => {})
    const user = userEvent.setup()
    render(<ProfilePage user={userProps} status={null} />)

    await user.click(dataTest('delete-user-button'))
    await user.click(screen.getByRole('button', { name: 'Cancel' }))

    expect(
      screen.queryByText(/are you sure you want to delete your account\?/i)
    ).not.toBeInTheDocument()
    expect(submitSpy).not.toHaveBeenCalled()

    submitSpy.mockRestore()
  })
})
