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
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)

  if (!user) return null

  const initials = getInitials(user.name)

  return (
    <>
      <Head title={title} />

      <div className="min-h-screen bg-white dark:bg-zinc-800">
        {/* Mobile sidebar overlay */}
        {sidebarOpen && (
          <div
            className="fixed inset-0 z-40 bg-black/50 lg:hidden"
            onClick={() => setSidebarOpen(false)}
          />
        )}

        {/* Sidebar */}
        <aside
          className={`fixed inset-y-0 left-0 z-50 w-64 border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 transform transition-transform duration-200 ease-in-out lg:translate-x-0 ${
            sidebarOpen ? 'translate-x-0' : '-translate-x-full'
          }`}
        >
          {/* Sidebar header */}
          <div className="flex h-14 items-center justify-between px-4 border-b border-zinc-200 dark:border-zinc-700">
            <Link
              href={route('dashboard')}
              className="flex items-center gap-2"
              onClick={() => setSidebarOpen(false)}
            >
              <span className="flex h-8 w-8 items-center justify-center bg-neutral-800 dark:bg-white text-white dark:text-black text-xs font-bold">
                E
              </span>
              <span className="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                EpochBridge
              </span>
            </Link>
            <button
              onClick={() => setSidebarOpen(false)}
              className="lg:hidden p-1 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
            >
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          {/* Navigation */}
          <nav className="flex flex-col gap-1 p-3">
            <p className="px-3 py-2 text-[0.625rem] uppercase tracking-[0.15em] font-medium text-zinc-500 dark:text-zinc-400">
              Platform
            </p>
            <Link
              href={route('dashboard')}
              onClick={() => setSidebarOpen(false)}
              className="flex items-center gap-3 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
            >
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              Dashboard
            </Link>
          </nav>

          {/* Sidebar spacer */}
          <div className="flex-1" />

          {/* Sidebar footer - user menu (desktop) */}
          <div className="hidden lg:block border-t border-zinc-200 dark:border-zinc-700 p-3">
            <div className="relative">
              <button
                onClick={() => setUserMenuOpen(!userMenuOpen)}
                className="w-full flex items-center gap-3 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                data-test="sidebar-menu-button"
              >
                <span className="flex h-8 w-8 items-center justify-center bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-white text-xs font-bold uppercase">
                  {initials}
                </span>
                <div className="flex-1 text-left">
                  <p className="truncate font-semibold text-sm">{user.name}</p>
                  <p className="truncate text-xs text-zinc-500 dark:text-zinc-400">{user.email}</p>
                </div>
                <svg className="w-4 h-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
                </svg>
              </button>

              {userMenuOpen && (
                <>
                  <div className="fixed inset-0 z-40" onClick={() => setUserMenuOpen(false)} />
                  <div className="absolute bottom-full left-0 mb-2 w-[220px] border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 z-50">
                    <div className="p-2">
                      <Link
                        href={route('profile.edit')}
                        onClick={() => { setUserMenuOpen(false); setSidebarOpen(false) }}
                        className="flex items-center gap-3 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                      >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Settings
                      </Link>
                    </div>
                    <div className="border-t border-zinc-200 dark:border-zinc-700 p-2">
                      <form method="POST" action={route('logout')}>
                        <input type="hidden" name="_token" value={(usePage().props as Record<string, unknown>).ziggy ? '' : ''} />
                        <button
                          type="submit"
                          className="w-full flex items-center gap-3 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                          data-test="logout-button"
                        >
                          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
          <header className="sticky top-0 z-30 flex h-14 items-center gap-4 border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 px-4 lg:hidden">
            <button
              onClick={() => setSidebarOpen(true)}
              className="p-1 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
            >
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>

            <div className="flex-1" />

            {/* Mobile user menu */}
            <div className="relative">
              <button
                onClick={() => setUserMenuOpen(!userMenuOpen)}
                className="flex h-8 w-8 items-center justify-center bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-white text-xs font-bold uppercase"
              >
                {initials}
              </button>

              {userMenuOpen && (
                <>
                  <div className="fixed inset-0 z-40" onClick={() => setUserMenuOpen(false)} />
                  <div className="absolute right-0 top-full mt-2 w-[220px] border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 z-50">
                    <div className="p-3 flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-700">
                      <span className="flex h-8 w-8 items-center justify-center bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-white text-xs font-bold uppercase">
                        {initials}
                      </span>
                      <div>
                        <p className="truncate font-semibold text-sm">{user.name}</p>
                        <p className="truncate text-xs text-zinc-500 dark:text-zinc-400">{user.email}</p>
                      </div>
                    </div>
                    <div className="p-2">
                      <Link
                        href={route('profile.edit')}
                        onClick={() => setUserMenuOpen(false)}
                        className="flex items-center gap-3 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                      >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Settings
                      </Link>
                    </div>
                    <div className="border-t border-zinc-200 dark:border-zinc-700 p-2">
                      <form method="POST" action={route('logout')}>
                        <button
                          type="submit"
                          className="w-full flex items-center gap-3 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                          data-test="logout-button"
                        >
                          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
