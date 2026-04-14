import { Head, Link } from '@inertiajs/react'
import type { ReactNode } from 'react'

interface AuthLayoutProps {
  children: ReactNode
  title?: string
}

export default function AuthLayout({ children, title }: AuthLayoutProps) {
  return (
    <>
      <Head title={title} />

      <div className="min-h-screen bg-white dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900 antialiased">
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
          <div className="flex w-full max-w-sm flex-col gap-4">
            <Link
              href="/"
              className="flex flex-col items-center gap-2 font-medium"
            >
              <span className="flex h-9 w-9 mb-1 items-center justify-center">
                <svg
                  className="size-9 fill-current text-black dark:text-white"
                  viewBox="0 0 100 100"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <rect x="15" y="15" width="70" height="70" fill="none" stroke="currentColor" strokeWidth="4" />
                  <text x="50" y="62" textAnchor="middle" fontSize="32" fontFamily="monospace" fill="currentColor">E</text>
                </svg>
              </span>
              <span className="sr-only">EpochBridge</span>
            </Link>
            <div className="flex flex-col gap-4 border-2 border-theme-300 dark:border-zinc-700 p-6 bg-white dark:bg-zinc-900">
              {children}
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
