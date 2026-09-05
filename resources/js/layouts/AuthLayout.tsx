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

      <div className="flex min-h-screen flex-col items-center justify-center p-6 antialiased md:p-10">
        <div className="flex w-full max-w-md flex-col">
          {/* Mini scenery sitting on the panel's top edge */}
          <div
            className="relative z-10 mx-auto -mb-3 flex items-end gap-2"
            aria-hidden="true"
          >
            <img
              src="/images/sd/grass-tuft.svg"
              width={32}
              height={32}
              className="sd-pixel"
              aria-hidden="true"
            />
            <Link href="/" className="flex flex-col items-center" aria-label="EpochBridge">
              <img
                src="/images/sd/logo-mark.svg"
                width={48}
                height={48}
                className="sd-pixel"
              />
            </Link>
            <img
              src="/images/sd/flower-pink.svg"
              width={32}
              height={32}
              className="sd-pixel"
              aria-hidden="true"
            />
            <img
              src="/images/sd/sprout.svg"
              width={24}
              height={24}
              className="sd-pixel"
              aria-hidden="true"
            />
          </div>

          <div className="sd-panel sd-pop flex flex-col gap-4 p-8">
            {children}
          </div>
        </div>
      </div>
    </>
  )
}
