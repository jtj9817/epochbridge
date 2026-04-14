import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'

// Mock Inertia
const mockPost = vi.fn()
const mockUseForm = vi.fn(() => ({
  data: { email: '', password: '', remember: false },
  setData: vi.fn(),
  post: mockPost,
  processing: false,
  errors: {},
  reset: vi.fn(),
}))

vi.mock('@inertiajs/react', () => ({
  Head: ({ title }: { title: string }) => <title data-testid="page-title">{title}</title>,
  Link: ({ children, href }: { children: React.ReactNode; href: string }) => (
    <a href={href}>{children}</a>
  ),
  useForm: () => mockUseForm(),
  usePage: () => ({
    props: {
      auth: { user: null },
      ziggy: { url: 'http://localhost', routes: {}, location: '/' },
    },
  }),
}))

// Mock route helper
vi.stubGlobal('route', (name: string) => `/${name}`)

// Import after mocks
import LoginPage from './LoginPage'
import RegisterPage from './RegisterPage'
import ForgotPasswordPage from './ForgotPasswordPage'

beforeEach(() => {
  vi.clearAllMocks()
  mockUseForm.mockReturnValue({
    data: { email: '', password: '', remember: false, name: '', password_confirmation: '' },
    setData: vi.fn(),
    post: mockPost,
    processing: false,
    errors: {},
    reset: vi.fn(),
  })
})

describe('LoginPage', () => {
  const defaultProps = {
    canResetPassword: true,
    canRegister: true,
    status: null,
    oauthError: null,
  }

  it('renders the login form', () => {
    render(<LoginPage {...defaultProps} />)

    expect(screen.getByText(/Log in to your account/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/Email address/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/Password/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /log in/i })).toBeInTheDocument()
  })

  it('shows status message when provided', () => {
    render(<LoginPage {...defaultProps} status="Password reset successfully!" />)

    expect(screen.getByText('Password reset successfully!')).toBeInTheDocument()
  })

  it('shows oauth error when provided', () => {
    render(<LoginPage {...defaultProps} oauthError="Authentication failed" />)

    expect(screen.getByText(/Authentication Error/i)).toBeInTheDocument()
    expect(screen.getByText(/Authentication failed/i)).toBeInTheDocument()
  })

  it('shows forgot password link when canResetPassword is true', () => {
    render(<LoginPage {...defaultProps} canResetPassword={true} />)

    expect(screen.getByText(/Forgot your password/i)).toBeInTheDocument()
  })

  it('hides forgot password link when canResetPassword is false', () => {
    render(<LoginPage {...defaultProps} canResetPassword={false} />)

    expect(screen.queryByText(/Forgot your password/i)).not.toBeInTheDocument()
  })

  it('shows register link when canRegister is true', () => {
    render(<LoginPage {...defaultProps} canRegister={true} />)

    expect(screen.getByText(/Sign up/i)).toBeInTheDocument()
  })

  it('hides register link when canRegister is false', () => {
    render(<LoginPage {...defaultProps} canRegister={false} />)

    expect(screen.queryByText(/Sign up/i)).not.toBeInTheDocument()
  })

  it('shows Google Sign-In button', () => {
    render(<LoginPage {...defaultProps} />)

    expect(screen.getByText(/Sign in with Google/i)).toBeInTheDocument()
  })

  it('has remember me checkbox', () => {
    render(<LoginPage {...defaultProps} />)

    expect(screen.getByLabelText(/Remember me/i)).toBeInTheDocument()
  })

  it('submits form when login button clicked', () => {
    render(<LoginPage {...defaultProps} />)

    const submitButton = screen.getByRole('button', { name: /log in/i })
    const form = submitButton.closest('form')
    if (form) {
      fireEvent.submit(form)
      expect(mockPost).toHaveBeenCalledWith('/login.store')
    }
  })
})

describe('RegisterPage', () => {
  it('renders the registration form', () => {
    render(<RegisterPage />)

    expect(screen.getByText(/Create an account/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/^Name$/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/Email/i)).toBeInTheDocument()
  })

  it('has password fields', () => {
    render(<RegisterPage />)

    // Get all password labels
    const passwordLabels = screen.getAllByText(/Password/i)
    expect(passwordLabels.length).toBeGreaterThanOrEqual(2)
  })

  it('has login link', () => {
    render(<RegisterPage />)

    expect(screen.getByRole('link', { name: /Log in/i })).toBeInTheDocument()
  })
})

describe('ForgotPasswordPage', () => {
  const defaultProps = {
    status: null,
  }

  it('renders the forgot password form', () => {
    render(<ForgotPasswordPage {...defaultProps} />)

    expect(screen.getByRole('heading', { name: /Forgot password/i })).toBeInTheDocument()
    expect(screen.getByLabelText(/Email/i)).toBeInTheDocument()
  })

  it('shows status message when link sent', () => {
    render(<ForgotPasswordPage status="A reset link will be sent if the email exists." />)

    expect(screen.getByText(/A reset link will be sent/i)).toBeInTheDocument()
  })

  it('has back to login link', () => {
    render(<ForgotPasswordPage {...defaultProps} />)

    // Text is split: "Or, return to " + "log in" link
    expect(screen.getByRole('link', { name: /log in/i })).toBeInTheDocument()
  })
})
