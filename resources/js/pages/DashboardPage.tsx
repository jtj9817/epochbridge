import { Head } from '@inertiajs/react'
import AppLayout from '@/layouts/AppLayout'

export default function DashboardPage() {
  return (
    <AppLayout title="Dashboard">
      <div className="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div className="grid auto-rows-min gap-4 md:grid-cols-3">
          <div className="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div className="absolute inset-0 size-full bg-zinc-50 dark:bg-zinc-800">
              <svg className="size-full stroke-gray-900/20 dark:stroke-neutral-100/20" viewBox="0 0 200 200">
                <defs>
                  <pattern id="grid1" width="20" height="20" patternUnits="userSpaceOnUse">
                    <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" strokeWidth="0.5" />
                  </pattern>
                </defs>
                <rect width="200" height="200" fill="url(#grid1)" />
              </svg>
            </div>
          </div>
          <div className="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div className="absolute inset-0 size-full bg-zinc-50 dark:bg-zinc-800">
              <svg className="size-full stroke-gray-900/20 dark:stroke-neutral-100/20" viewBox="0 0 200 200">
                <defs>
                  <pattern id="grid2" width="20" height="20" patternUnits="userSpaceOnUse">
                    <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" strokeWidth="0.5" />
                  </pattern>
                </defs>
                <rect width="200" height="200" fill="url(#grid2)" />
              </svg>
            </div>
          </div>
          <div className="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div className="absolute inset-0 size-full bg-zinc-50 dark:bg-zinc-800">
              <svg className="size-full stroke-gray-900/20 dark:stroke-neutral-100/20" viewBox="0 0 200 200">
                <defs>
                  <pattern id="grid3" width="20" height="20" patternUnits="userSpaceOnUse">
                    <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" strokeWidth="0.5" />
                  </pattern>
                </defs>
                <rect width="200" height="200" fill="url(#grid3)" />
              </svg>
            </div>
          </div>
        </div>
        <div className="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
          <div className="absolute inset-0 size-full bg-zinc-50 dark:bg-zinc-800">
            <svg className="size-full stroke-gray-900/20 dark:stroke-neutral-100/20" viewBox="0 0 200 200">
              <defs>
                <pattern id="grid4" width="20" height="20" patternUnits="userSpaceOnUse">
                  <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" strokeWidth="0.5" />
                </pattern>
              </defs>
              <rect width="200" height="200" fill="url(#grid4)" />
            </svg>
          </div>
        </div>
      </div>
    </AppLayout>
  )
}
