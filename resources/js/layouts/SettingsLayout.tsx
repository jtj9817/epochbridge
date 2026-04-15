import { Head, Link, usePage } from '@inertiajs/react'
import { type ReactNode } from 'react'

interface SettingsLayoutProps {
  children: ReactNode
  heading: string
  subheading: string
  title?: string
}

export default function SettingsLayout({ children, heading, subheading, title }: SettingsLayoutProps) {
  return (
    <>
      {title && <Head title={title} />}

      {/* Settings heading */}
      <div className="relative mb-6 w-full border-b-2 border-theme-300 dark:border-zinc-700 pb-4">
        <h1 className="text-lg font-bold text-theme-900 dark:text-theme-50 uppercase tracking-widest">
          Settings
        </h1>
        <p className="text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-wider mt-1">
          Manage your profile and account settings
        </p>
      </div>

      {/* Settings navigation tabs */}
      <div className="flex gap-1 mb-6 border-b border-zinc-200 dark:border-zinc-700">
        <SettingsTab href={route('profile.edit')} label="Profile" />
        <SettingsTab href={route('password.edit')} label="Password" />
        <SettingsTab href={route('appearance.edit')} label="Appearance" />
        <SettingsTab href={route('two-factor.show')} label="Two Factor" />
      </div>

      {/* Section heading */}
      <div className="mb-6">
        <h2 className="text-lg font-semibold text-theme-900 dark:text-theme-50">{heading}</h2>
        <p className="text-sm text-theme-500 dark:text-zinc-400 mt-1">{subheading}</p>
      </div>

      {children}
    </>
  )
}

function SettingsTab({ href, label }: { href: string; label: string }) {
  const page = usePage()
  const isActive = page.url === href

  return (
    <Link
      href={href}
      className={`px-4 py-2 text-xs uppercase tracking-wider font-medium transition-colors ${
        isActive
          ? 'text-theme-900 dark:text-theme-50 border-b-2 border-theme-500 dark:border-theme-100'
          : 'text-theme-500 dark:text-zinc-400 hover:text-theme-700 dark:hover:text-zinc-200'
      }`}
    >
      {label}
    </Link>
  )
}
