import { Head, Link, usePage } from '@inertiajs/react'
import { type ReactNode, useState } from 'react'

interface AppLayoutProps {
  children: ReactNode
  title?: string
}

function getInitials(name: string): string {
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

export default function AppLayout({ children, title }: AppLayoutProps) {
  const { auth } = usePage().props
  const user = auth.user
  const pageUrl = usePage().url
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)

  if (!user) return null

  const initials = getInitials(user.name)
  const currentPath = pageUrl.split('?')[0]
  const dashboardPath = new URL(route('dashboard'), window.location.origin).pathname
  const isDashboardActive = currentPath === dashboardPath || currentPath === '/'
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

  return (
    <>
      <Head title={title} />

      <div className="min-h-screen">
        {/* Mobile sidebar overlay */}
        {sidebarOpen && (
          <div
            className="sd-overlay fixed inset-0 z-40 lg:hidden"
            onClick={() => setSidebarOpen(false)}
          />
        )}

        {/* Sidebar */}
        <aside
          className={`fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-[color:var(--sd-wood-shade)] transform transition-transform duration-200 ease-in-out lg:translate-x-0 ${
            sidebarOpen ? 'translate-x-0' : '-translate-x-full'
          }`}
          style={{ backgroundColor: 'color-mix(in srgb, var(--sd-wood-shade) 35%, transparent)' }}
        >
          {/* Sidebar header */}
          <div className="flex h-16 items-center justify-between gap-2 border-b border-[color:var(--sd-wood-shade)] px-4">
            <Link
              href={route('dashboard')}
              className="flex items-center gap-2"
              onClick={() => setSidebarOpen(false)}
            >
              <img
                src="/images/sd/logo-mark.svg"
                width={40}
                height={40}
                className="sd-pixel"
                aria-hidden="true"
              />
              <span className="sd-h3 text-[color:var(--sd-cream)]">
                EpochBridge
              </span>
            </Link>
            <button
              onClick={() => setSidebarOpen(false)}
              className="lg:hidden"
              aria-label="Close sidebar"
            >
              <img
                src="/images/sd/icon-x.svg"
                width={16}
                height={16}
                className="sd-pixel"
                aria-hidden="true"
              />
            </button>
          </div>

          {/* Navigation */}
          <nav className="flex flex-col gap-2 p-3">
            <p className="sd-label px-3 py-2 text-[color:var(--sd-cream)] opacity-80">
              Platform
            </p>
            <Link
              href={route('dashboard')}
              onClick={() => setSidebarOpen(false)}
              className={`sd-btn w-full ${
                isDashboardActive ? 'sd-btn--gold' : 'sd-btn--ghost'
              }`}
              style={{ justifyContent: 'flex-start' }}
            >
              <img
                src="/images/sd/icon-tilestar.svg"
                width={16}
                height={16}
                className="sd-pixel"
                aria-hidden="true"
              />
              Dashboard
            </Link>
          </nav>

          {/* Sidebar spacer */}
          <div className="flex-1" />

          {/* Sidebar footer - user menu (desktop) */}
          <div className="hidden border-t border-[color:var(--sd-wood-shade)] p-3 lg:block">
            <div className="relative">
              <button
                onClick={() => setUserMenuOpen(!userMenuOpen)}
                className="flex w-full items-center gap-3 p-1 text-left"
                data-test="sidebar-menu-button"
              >
                <span className="sd-slot flex h-10 w-10 shrink-0 items-center justify-center">
                  <span className="sd-data-md text-[color:var(--sd-gold)]">
                    {initials}
                  </span>
                </span>
                <div className="min-w-0 flex-1">
                  <p className="sd-small truncate text-[color:var(--sd-cream)]">{user.name}</p>
                  <p className="sd-data-sm truncate text-[color:var(--sd-cream)] opacity-70">{user.email}</p>
                </div>
                <svg className="w-4 h-4 shrink-0 text-[color:var(--sd-cream)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
                </svg>
              </button>

              {userMenuOpen && (
                <>
                  <div className="fixed inset-0 z-40" onClick={() => setUserMenuOpen(false)} />
                  <div className="sd-panel sd-pop absolute bottom-full left-0 z-50 mb-2 w-[220px] p-2">
                    <div>
                      <Link
                        href={route('profile.edit')}
                        onClick={() => { setUserMenuOpen(false); setSidebarOpen(false) }}
                        className="sd-btn sd-btn--ghost sd-btn--sm w-full"
                        style={{ justifyContent: 'flex-start' }}
                      >
                        <img
                          src="/images/sd/icon-gear.svg"
                          width={16}
                          height={16}
                          className="sd-pixel"
                          aria-hidden="true"
                        />
                        Settings
                      </Link>
                    </div>
                    <div className="my-1 h-0.5 bg-[color:var(--sd-parch-shadow)]" />
                    <div>
                      <form method="POST" action={route('logout')}>
                        <input type="hidden" name="_token" value={csrfToken} />
                        <button
                          type="submit"
                          className="sd-btn sd-btn--ghost sd-btn--sm w-full"
                          style={{ justifyContent: 'flex-start' }}
                          data-test="logout-button"
                        >
                          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                          </svg>
                          Log Out
                        </button>
                      </form>
                    </div>
                  </div>
                </>
              )}
            </div>
          </div>
        </aside>

        {/* Main content area */}
        <div className="lg:pl-64">
          {/* Top header (mobile) */}
          <header
            className="sticky top-0 z-30 flex h-14 items-center gap-4 border-b border-[color:var(--sd-wood-shade)] px-4 lg:hidden"
            style={{ backgroundColor: 'color-mix(in srgb, var(--sd-wood-shade) 65%, transparent)' }}
          >
            <button
              onClick={() => setSidebarOpen(true)}
              aria-label="Open sidebar"
              className="text-[color:var(--sd-cream)]"
            >
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>

            <div className="flex-1" />

            {/* Mobile user menu */}
            <div className="relative">
              <button
                onClick={() => setUserMenuOpen(!userMenuOpen)}
                className="sd-slot flex h-9 w-9 items-center justify-center"
                aria-label="Open user menu"
              >
                <span className="sd-data-sm text-[color:var(--sd-gold)]">
                  {initials}
                </span>
              </button>

              {userMenuOpen && (
                <>
                  <div className="fixed inset-0 z-40" onClick={() => setUserMenuOpen(false)} />
                  <div className="sd-panel sd-pop absolute right-0 top-full z-50 mt-2 w-[220px] p-2">
                    <div className="flex items-center gap-3 p-1">
                      <span className="sd-slot flex h-10 w-10 shrink-0 items-center justify-center">
                        <span className="sd-data-md text-[color:var(--sd-gold)]">
                          {initials}
                        </span>
                      </span>
                      <div className="min-w-0">
                        <p className="sd-small truncate text-[color:var(--sd-ink)]">{user.name}</p>
                        <p className="sd-data-sm truncate text-[color:var(--sd-ink-soft)]">{user.email}</p>
                      </div>
                    </div>
                    <div className="my-1 h-0.5 bg-[color:var(--sd-parch-shadow)]" />
                    <div>
                      <Link
                        href={route('profile.edit')}
                        onClick={() => setUserMenuOpen(false)}
                        className="sd-btn sd-btn--ghost sd-btn--sm w-full"
                        style={{ justifyContent: 'flex-start' }}
                      >
                        <img
                          src="/images/sd/icon-gear.svg"
                          width={16}
                          height={16}
                          className="sd-pixel"
                          aria-hidden="true"
                        />
                        Settings
                      </Link>
                    </div>
                    <div className="my-1 h-0.5 bg-[color:var(--sd-parch-shadow)]" />
                    <div>
                      <form method="POST" action={route('logout')}>
                        <input type="hidden" name="_token" value={csrfToken} />
                        <button
                          type="submit"
                          className="sd-btn sd-btn--ghost sd-btn--sm w-full"
                          style={{ justifyContent: 'flex-start' }}
                          data-test="logout-button"
                        >
                          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                          </svg>
                          Log Out
                        </button>
                      </form>
                    </div>
                  </div>
                </>
              )}
            </div>
          </header>

          {/* Page content */}
          <main className="p-6">
            {children}
          </main>
        </div>
      </div>
    </>
  )
}
