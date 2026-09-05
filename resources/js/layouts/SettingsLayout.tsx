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
      <div className="relative mb-6 w-full">
        <h1 className="sd-h2 text-[color:var(--sd-ink)]">
          Settings
        </h1>
        <p className="sd-small mt-1 text-[color:var(--sd-ink-soft)]">
          Manage your profile and account settings
        </p>
      </div>

      {/* Settings navigation tabs (docked onto the panel below) */}
      <div className="flex gap-1">
        <SettingsTab href={route('profile.edit')} label="Profile" />
        <SettingsTab href={route('password.edit')} label="Password" />
        <SettingsTab href={route('appearance.edit')} label="Appearance" />
        <SettingsTab href={route('two-factor.show')} label="Two Factor" />
      </div>

      {/* Content panel */}
      <div className="sd-panel p-6">
        {/* Section heading */}
        <div className="mb-6">
          <h2 className="sd-h3 text-[color:var(--sd-ink)]">{heading}</h2>
          <p className="sd-small mt-1 text-[color:var(--sd-ink-soft)]">{subheading}</p>
        </div>

        {children}
      </div>
    </>
  )
}

function SettingsTab({ href, label }: { href: string; label: string }) {
  const page = usePage()
  const isActive = page.url === href

  return (
    <Link
      href={href}
      className={`sd-tab ${isActive ? 'sd-tab--active' : ''}`}
    >
      {label}
    </Link>
  )
}
