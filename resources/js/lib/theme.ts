type AppearanceMode = 'light' | 'dark' | 'system'

function isPublicDarkRoute(pathname: string): boolean {
  return (
    pathname === '/' ||
    pathname === '/login' ||
    pathname === '/register' ||
    pathname === '/forgot-password' ||
    pathname.startsWith('/reset-password/') ||
    pathname === '/two-factor-challenge' ||
    pathname === '/verify-email' ||
    pathname === '/confirm-password'
  )
}

function getAppearanceMode(): AppearanceMode {
  try {
    const stored = window.localStorage.getItem('appearance')
    if (stored === 'light' || stored === 'dark' || stored === 'system') {
      return stored
    }
  } catch {
    return 'system'
  }

  return 'system'
}

function shouldEnableDarkMode(pathname: string): boolean {
  if (isPublicDarkRoute(pathname)) {
    return true
  }

  const appearance = getAppearanceMode()
  if (appearance === 'dark') {
    return true
  }
  if (appearance === 'light') {
    return false
  }

  return window.matchMedia('(prefers-color-scheme: dark)').matches
}

export function applyTheme(pathname: string = window.location.pathname): void {
  document.documentElement.classList.toggle('dark', shouldEnableDarkMode(pathname))
}

