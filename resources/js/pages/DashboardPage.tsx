import { Head, usePage } from '@inertiajs/react'
import AppLayout from '@/layouts/AppLayout'

export default function DashboardPage() {
  const { auth } = usePage().props
  const user = auth.user

  const todayLabel = new Date().toLocaleDateString(undefined, {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
  const unixNow = Math.floor(Date.now() / 1000)
  const julianDay = Math.floor(unixNow / 86400) + 2440588

  return (
    <AppLayout title="Dashboard">
      <div className="flex h-full w-full flex-1 flex-col gap-4">
        {/* Greeting */}
        <div className="sd-panel p-6">
          <h1 className="sd-h2 text-[color:var(--sd-ink)]">
            Welcome back, {user ? user.name : 'traveler'}
          </h1>
          <p className="sd-data-sm mt-2 text-[color:var(--sd-ink-soft)]">{todayLabel}</p>
        </div>

        {/* Stat row */}
        <div className="grid auto-rows-min gap-4 md:grid-cols-3">
          <div className="sd-slot flex items-center gap-4 p-4">
            <img
              src="/images/sd/icon-calendar.svg"
              width={32}
              height={32}
              className="sd-pixel"
              aria-hidden="true"
            />
            <div>
              <p className="sd-label text-[color:var(--sd-cream)] opacity-70">Calendars</p>
              <p className="sd-data-md text-[color:var(--sd-cream)]">19</p>
            </div>
          </div>
          <div className="sd-slot flex items-center gap-4 p-4">
            <img
              src="/images/sd/icon-clock.svg"
              width={32}
              height={32}
              className="sd-pixel"
              aria-hidden="true"
            />
            <div>
              <p className="sd-label text-[color:var(--sd-cream)] opacity-70">Unix Time</p>
              <p className="sd-data-md text-[color:var(--sd-cream)]">{unixNow}</p>
            </div>
          </div>
          <div className="sd-slot flex items-center gap-4 p-4">
            <img
              src="/images/sd/icon-tilestar.svg"
              width={32}
              height={32}
              className="sd-pixel"
              aria-hidden="true"
            />
            <div>
              <p className="sd-label text-[color:var(--sd-cream)] opacity-70">Julian Day</p>
              <p className="sd-data-md text-[color:var(--sd-cream)]">{julianDay}</p>
            </div>
          </div>
        </div>

        {/* Recent items area */}
        <div className="sd-panel flex h-full min-h-48 flex-1 flex-col items-center justify-center gap-3 p-6">
          <div className="flex items-end gap-3" aria-hidden="true">
            <img
              src="/images/sd/grass-tuft.svg"
              width={32}
              height={32}
              className="sd-pixel"
              aria-hidden="true"
            />
            <img
              src="/images/sd/icon-scroll.svg"
              width={32}
              height={32}
              className="sd-pixel"
              aria-hidden="true"
            />
            <img
              src="/images/sd/sparkle.svg"
              width={16}
              height={16}
              className="sd-pixel"
              aria-hidden="true"
            />
            <img
              src="/images/sd/flower-yellow.svg"
              width={16}
              height={16}
              className="sd-pixel"
              aria-hidden="true"
            />
          </div>
          <img
            src="/images/sd/hills-strip.svg"
            width={32}
            height={32}
            className="sd-pixel"
            aria-hidden="true"
          />
        </div>
      </div>
    </AppLayout>
  )
}
