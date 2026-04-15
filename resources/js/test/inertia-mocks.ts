import { vi } from 'vitest'
import type { MockPageProps } from './utils'

// Mock Inertia router
export const mockRouter = {
  visit: vi.fn(),
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  patch: vi.fn(),
  delete: vi.fn(),
  reload: vi.fn(),
  on: vi.fn(() => () => {}),
}

// Mock useForm return value
export const createMockUseForm = <T extends Record<string, unknown>>(
  initialData: T
) => ({
  data: initialData,
  setData: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  patch: vi.fn(),
  delete: vi.fn(),
  processing: false,
  errors: {} as Record<keyof T, string>,
  reset: vi.fn(),
  clearErrors: vi.fn(),
  setError: vi.fn(),
  hasErrors: false,
  recentlySuccessful: false,
  transform: vi.fn(),
  submit: vi.fn(),
  cancel: vi.fn(),
  progress: null,
  isDirty: false,
  wasSuccessful: false,
})

// Create mock usePage hook
export const createMockUsePage = (props: MockPageProps = {}) => ({
  props: {
    auth: props.auth ?? { user: null },
    ziggy: props.ziggy ?? {
      url: 'http://localhost',
      port: null,
      defaults: {},
      routes: {},
      location: 'http://localhost',
    },
    flash: props.flash,
    ...props,
  },
  url: '/',
  component: 'TestPage',
  version: null,
  scrollRegions: [],
  rememberedState: {},
  resolvedErrors: {},
})

// Setup Inertia mocks - call this in your test file
export function setupInertiaMocks(pageProps: MockPageProps = {}) {
  vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual('@inertiajs/react')
    return {
      ...actual,
      router: mockRouter,
      usePage: () => createMockUsePage(pageProps),
      useForm: <T extends Record<string, unknown>>(initialData: T) =>
        createMockUseForm(initialData),
      Head: ({ children, title }: { children?: React.ReactNode; title?: string }) => null,
      Link: ({ children, href, ...props }: { children: React.ReactNode; href: string; [key: string]: unknown }) => (
        <a href={href} {...props}>{children}</a>
      ),
    }
  })
}

// Mock route helper (Ziggy)
export const mockRoute = vi.fn((name: string, params?: Record<string, unknown>) => {
  const routes: Record<string, string> = {
    'home': '/',
    'login': '/login',
    'login.store': '/login',
    'register': '/register',
    'register.store': '/register',
    'logout': '/logout',
    'dashboard': '/dashboard',
    'password.request': '/forgot-password',
    'password.email': '/forgot-password',
    'password.reset': '/reset-password',
    'password.update': '/reset-password',
    'password.confirm': '/confirm-password',
    'verification.notice': '/verify-email',
    'verification.send': '/email/verification-notification',
    'two-factor.login': '/two-factor-challenge',
    'two-factor.login.store': '/two-factor-challenge',
    'two-factor.enable': '/user/two-factor-authentication',
    'two-factor.confirm': '/user/confirmed-two-factor-authentication',
    'two-factor.disable': '/user/two-factor-authentication',
    'profile.edit': '/settings/profile',
    'user-profile-information.update': '/user/profile-information',
    'user-password-update': '/user/password',
    'current-user.destroy': '/user',
    'social.redirect': `/auth/${params?.provider ?? 'google'}/redirect`,
  }
  return routes[name] ?? `/${name}`
})

// Setup route mock globally
vi.stubGlobal('route', mockRoute)
