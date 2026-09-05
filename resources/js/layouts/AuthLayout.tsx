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

      <div className="flex min-h-screen flex-col items-center justify-center gap-6 p-6 antialiased md:p-10">
        {/* Mini scenery */}
        <div className="flex items-end gap-2" aria-hidden="true">
          <img
            src="/images/sd/grass-tuft.svg"
            width={32}
            height={32}
            className="sd-pixel"
            aria-hidden="true"
          />
          <img
            src="/images/sd/flower-pink.svg"
            width={16}
            height={16}
            className="sd-pixel"
            aria-hidden="true"
          />
          <img
            src="/images/sd/sprout.svg"
            width={16}
            height={16}
            className="sd-pixel"
            aria-hidden="true"
          />
        </div>

        <div className="flex w-full max-w-sm flex-col gap-4">
          <Link
            href="/"
            className="flex flex-col items-center gap-2 font-medium"
          >
            <img
              src="/images/sd/logo-mark.svg"
              width={32}
              height={32}
              className="sd-pixel"
              aria-hidden="true"
            />
            <span className="sr-only">EpochBridge</span>
          </Link>
          <div className="sd-panel flex flex-col gap-4 p-8">
            {children}
          </div>
        </div>
      </div>
    </>
  )
}
