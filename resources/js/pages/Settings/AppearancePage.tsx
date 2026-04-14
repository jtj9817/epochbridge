import { Head } from '@inertiajs/react'
import AppLayout from '@/layouts/AppLayout'
import SettingsLayout from '@/layouts/SettingsLayout'
import { useState, useEffect } from 'react'

type AppearanceMode = 'light' | 'dark' | 'system'

export default function AppearancePage() {
  const [mode, setMode] = useState<AppearanceMode>(() => {
    if (typeof window !== 'undefined') {
      const stored = localStorage.getItem('appearance')
      if (stored === 'light' || stored === 'dark' || stored === 'system') {
        return stored
      }
    }
    return 'system'
  })

  useEffect(() => {
    localStorage.setItem('appearance', mode)
    applyMode(mode)
  }, [mode])

  function applyMode(m: AppearanceMode) {
    const root = document.documentElement
    if (m === 'dark') {
      root.classList.add('dark')
    } else if (m === 'light') {
      root.classList.remove('dark')
    } else {
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      if (prefersDark) {
        root.classList.add('dark')
      } else {
        root.classList.remove('dark')
      }
    }
  }

  return (
    <AppLayout title="Appearance Settings">
      <SettingsLayout
        heading="Appearance"
        subheading="Update the appearance settings for your account"
      >
        <div className="flex gap-1 mt-6">
          <button
            onClick={() => setMode('light')}
            className={`flex items-center gap-2 px-4 py-3 border text-xs uppercase tracking-wider font-medium transition-colors ${
              mode === 'light'
                ? 'border-theme-500 bg-theme-50 dark:bg-zinc-800 text-theme-900 dark:text-theme-50'
                : 'border-theme-300 dark:border-zinc-600 text-theme-500 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800'
            }`}
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            Light
          </button>
          <button
            onClick={() => setMode('dark')}
            className={`flex items-center gap-2 px-4 py-3 border text-xs uppercase tracking-wider font-medium transition-colors ${
              mode === 'dark'
                ? 'border-theme-500 bg-theme-50 dark:bg-zinc-800 text-theme-900 dark:text-theme-50'
                : 'border-theme-300 dark:border-zinc-600 text-theme-500 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800'
            }`}
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
            Dark
          </button>
          <button
            onClick={() => setMode('system')}
            className={`flex items-center gap-2 px-4 py-3 border text-xs uppercase tracking-wider font-medium transition-colors ${
              mode === 'system'
                ? 'border-theme-500 bg-theme-50 dark:bg-zinc-800 text-theme-900 dark:text-theme-50'
                : 'border-theme-300 dark:border-zinc-600 text-theme-500 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800'
            }`}
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            System
          </button>
        </div>
      </SettingsLayout>
    </AppLayout>
  )
}
